<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CatalogImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $businessIds = $this->manageableBusinessIds($request);

        $products = Product::query()
            ->with(['business', 'category'])
            ->when($businessIds !== null, fn ($query) => $query->whereIn('business_id', $businessIds ?: [0]))
            ->when($request->integer('business_id'), fn ($query, $id) => $query->where('business_id', $id))
            ->when($request->string('q')->toString(), fn ($query, $term) => $query->where('name_ar', 'like', "%{$term}%"))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'businesses' => $this->catalogBusinesses($request),
            'productCategories' => ProductCategory::query()
                ->when($businessIds !== null, fn ($query) => $query->whereIn('business_id', $businessIds ?: [0]))
                ->orderBy('name_ar')
                ->get(['id', 'business_id', 'name_ar']),
            'filters' => $request->only(['q', 'business_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        Product::query()->create($this->validated($request));

        return back()->with('success', __('تم إنشاء المنتج.'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $product->update($this->validated($request, $product));

        return back()->with('success', __('تم تحديث المنتج.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'product_category_id' => $request->filled('product_category_id') ? $request->input('product_category_id') : null,
            'category_name' => $request->filled('category_name') ? $request->input('category_name') : null,
        ]);

        $data = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => CatalogImage::rules(),
        ]);

        $this->assertCanManageBusinessCatalog($request, (int) $data['business_id']);

        if (blank($data['product_category_id'] ?? null) && blank($data['category_name'] ?? null)) {
            throw ValidationException::withMessages([
                'product_category_id' => __('اختر تصنيفاً أو اكتب تصنيفاً جديداً للمنتج.'),
            ]);
        }

        if (filled($data['category_name'] ?? null)) {
            $category = ProductCategory::query()->firstOrCreate(
                [
                    'business_id' => $data['business_id'],
                    'name_ar' => $data['category_name'],
                ],
                ['is_active' => true],
            );
            $data['product_category_id'] = $category->id;
        }

        $category = ProductCategory::query()->findOrFail($data['product_category_id']);

        abort_unless($category->business_id === (int) $data['business_id'], 422);

        unset($data['category_name']);

        $data['is_available'] = $request->boolean('is_available', true);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image_path'] = CatalogImage::store(
                $request->file('image'),
                'products',
                $product?->image_path,
                CatalogImage::PRODUCT_MAX_WIDTH,
            );
        }

        unset($data['image']);

        return $data;
    }

    /**
     * @return list<int>|null
     */
    private function manageableBusinessIds(Request $request): ?array
    {
        $user = $request->user();

        if ($user->hasRole('super_admin') || $user->can('businesses.manage')) {
            return null;
        }

        return Business::query()
            ->where('manager_user_id', $user->id)
            ->pluck('id')
            ->all();
    }

    /**
     * @return Collection<int, Business>
     */
    private function catalogBusinesses(Request $request)
    {
        $ids = $this->manageableBusinessIds($request);

        return Business::query()
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids ?: [0]))
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);
    }

    private function assertCanManageBusinessCatalog(Request $request, int $businessId): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin') || $user->can('businesses.manage')) {
            return;
        }

        abort_unless(
            Business::query()->whereKey($businessId)->where('manager_user_id', $user->id)->exists(),
            403,
        );
    }
}
