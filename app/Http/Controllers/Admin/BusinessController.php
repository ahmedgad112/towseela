<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\CatalogImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Business::class);

        $businesses = Business::query()
            ->with('category')
            ->withCount(['products', 'productCategories'])
            ->when($request->string('q')->toString(), fn ($query, $term) => $query->where('name_ar', 'like', "%{$term}%"))
            ->orderBy('name_ar')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Businesses/Index', [
            'businesses' => $businesses,
            'filters' => $request->only('q'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Business::class);

        return Inertia::render('Admin/Businesses/Form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Business::class);

        $payload = $this->withCover($request, $this->validated($request));
        $hours = $this->validatedHours($request, required: true);
        $branch = $this->validatedBranch($request);
        $products = $this->validatedProducts($request);

        $business = DB::transaction(function () use ($payload, $hours, $branch, $products) {
            $business = Business::query()->create($payload);
            $this->applyHours($business, $hours);
            $this->applyBranch($business, $branch);
            $this->applyProducts($business, $products);

            return $business;
        });

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', __('تم إنشاء المتجر. أضف تصنيفاته ومنتجاته من هنا.'));
    }

    public function show(Request $request, Business $business): Response
    {
        $this->authorize('view', $business);

        $business->load([
            'category',
            'manager',
            'branches.deliveryZone',
            'productCategories' => fn ($query) => $query->withCount('products')->orderBy('sort_order')->orderBy('name_ar'),
            'products' => fn ($query) => $query->with('category')->orderBy('name_ar')->orderBy('id'),
        ]);

        return Inertia::render('Admin/Businesses/Show', [
            'business' => $business,
            'canManageCatalog' => $this->canManageCatalog($request, $business),
        ]);
    }

    public function edit(Business $business): Response
    {
        $this->authorize('update', $business);

        return Inertia::render('Admin/Businesses/Form', $this->formData($business->load([
            'branches.deliveryZone',
            'hours',
        ])));
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $this->authorize('update', $business);

        $payload = $this->withCover($request, $this->validated($request, $business), $business);
        $hours = $this->validatedHours($request, required: true);

        $business->update($payload);
        $this->applyHours($business, $hours);

        return back()->with('success', __('تم تحديث المتجر.'));
    }

    public function storeCover(Request $request, Business $business): RedirectResponse
    {
        $this->authorize('update', $business);

        $request->validate([
            'cover' => CatalogImage::rules(required: true),
        ]);

        $business->update([
            'cover_path' => CatalogImage::store($request->file('cover'), 'businesses', $business->cover_path),
        ]);

        return back()->with('success', __('تم حفظ صورة المتجر.'));
    }

    public function storeBranch(Request $request, Business $business): RedirectResponse
    {
        $this->authorize('update', $business);
        $this->applyBranch($business, $this->validatedBranch($request, required: true));

        return back()->with('success', __('تم إضافة الفرع.'));
    }

    public function storeProduct(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);
        $this->applyProducts($business, [$this->productInput($request)]);

        return back()->with('success', __('تم إضافة المنتج للمتجر.'));
    }

    public function updateProduct(Request $request, Business $business, Product $product): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);
        $product->update($this->productAttributes($business, $this->productInput($request, $product)));

        return back()->with('success', __('تم تحديث المنتج.'));
    }

    public function toggleProductAvailability(Request $request, Business $business, Product $product): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);

        $available = ! $product->is_available;
        $product->update(['is_available' => $available]);

        return back()->with('success', $available ? __('المنتج متاح الآن.') : __('تم إيقاف المنتج مؤقتاً.'));
    }

    public function destroyProduct(Request $request, Business $business, Product $product): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);
        CatalogImage::delete($product->image_path);
        $product->delete();

        return back()->with('success', __('تم حذف المنتج.'));
    }

    public function storeCategory(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);
        $business->productCategories()->create($this->categoryInput($request));

        return back()->with('success', __('تم إضافة التصنيف.'));
    }

    public function updateCategory(Request $request, Business $business, ProductCategory $productCategory): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);
        $productCategory->update($this->categoryInput($request));

        return back()->with('success', __('تم تحديث التصنيف.'));
    }

    public function destroyCategory(Request $request, Business $business, ProductCategory $productCategory): RedirectResponse
    {
        $this->authorizeCatalog($request, $business);

        if ($productCategory->products()->exists()) {
            return back()->with('error', __('احذف منتجات هذا التصنيف أولاً قبل حذفه.'));
        }

        $productCategory->delete();

        return back()->with('success', __('تم حذف التصنيف.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Business $business = null): array
    {
        return [
            'business' => $business,
            'categories' => BusinessCategory::query()->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'name_ar']),
            'managers' => User::role('business_manager')->orderBy('name')->get(['id', 'name']),
            'zones' => DeliveryZone::query()
                ->active()
                ->orderBy('governorate')
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'governorate']),
            'hours' => $this->hoursPayload($business),
        ];
    }

    /**
     * @return list<array{day_of_week: int, opens_at: string, closes_at: string, is_closed: bool}>
     */
    private function hoursPayload(?Business $business): array
    {
        $existing = $business?->hours?->keyBy('day_of_week') ?? collect();

        return collect(range(0, 6))->map(function (int $day) use ($existing): array {
            $hour = $existing->get($day);

            return [
                'day_of_week' => $day,
                'opens_at' => $this->timeValue($hour?->opens_at, '10:00'),
                'closes_at' => $this->timeValue($hour?->closes_at, '23:30'),
                'is_closed' => (bool) ($hour?->is_closed ?? false),
            ];
        })->all();
    }

    private function timeValue(mixed $value, string $fallback): string
    {
        if (! $value) {
            return $fallback;
        }

        return substr((string) $value, 0, 5);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Business $business = null): array
    {
        $data = $request->validate([
            'business_category_id' => ['required', 'exists:business_categories,id'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'accepts_orders' => ['sometimes', 'boolean'],
            'min_order_amount' => ['required', 'numeric', 'min:0'],
            'delivery_time_min' => ['required', 'integer', 'min:5'],
            'delivery_time_max' => ['required', 'integer', 'min:5', 'gte:delivery_time_min'],
        ]);

        $data['slug'] = $business?->slug ?: $this->uniqueSlug((string) (($data['name_en'] ?? '') ?: $data['name_ar']));
        $data['is_active'] = $request->boolean('is_active', true);
        $data['accepts_orders'] = $request->boolean('accepts_orders', true);

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'store-'.Str::lower(Str::random(6));
        }

        $slug = $base;
        $suffix = 1;

        while (Business::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return list<array{day_of_week: int, opens_at: ?string, closes_at: ?string, is_closed: bool}>
     */
    private function validatedHours(Request $request, bool $required = false): array
    {
        $hours = $request->validate([
            'hours' => [$required ? 'required' : 'nullable', 'array'],
            'hours.*.day_of_week' => ['required_with:hours', 'integer', 'between:0,6'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['sometimes', 'boolean'],
        ])['hours'] ?? null;

        if ($hours === null) {
            return collect(range(0, 6))->map(fn (int $day): array => [
                'day_of_week' => $day,
                'opens_at' => '10:00',
                'closes_at' => '23:30',
                'is_closed' => false,
            ])->all();
        }

        return $hours;
    }

    /**
     * @return array{delivery_zone_id: mixed, branch_name: mixed, branch_address: mixed, branch_phone: mixed}|null
     */
    private function validatedBranch(Request $request, bool $required = false): ?array
    {
        $data = $request->validate([
            'delivery_zone_id' => [$required ? 'required' : 'nullable', 'exists:delivery_zones,id'],
            'branch_name' => [$required ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_address' => [$required ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_phone' => ['nullable', 'string', 'max:20'],
        ]);

        if (! $required && blank($data['branch_address'] ?? null)) {
            return null;
        }

        return $data;
    }

    /**
     * @param  list<array{day_of_week: int, opens_at: ?string, closes_at: ?string, is_closed: bool}>  $hours
     */
    private function applyHours(Business $business, array $hours): void
    {
        foreach ($hours as $hour) {
            $closed = (bool) ($hour['is_closed'] ?? false);

            $business->hours()->updateOrCreate(
                [
                    'day_of_week' => (int) $hour['day_of_week'],
                    'business_branch_id' => null,
                ],
                [
                    'opens_at' => $closed ? null : ($hour['opens_at'] ?? '10:00'),
                    'closes_at' => $closed ? null : ($hour['closes_at'] ?? '23:30'),
                    'is_closed' => $closed,
                ],
            );
        }
    }

    /**
     * @param  array{delivery_zone_id: mixed, branch_name: mixed, branch_address: mixed, branch_phone: mixed}|null  $data
     */
    private function applyBranch(Business $business, ?array $data): void
    {
        if ($data === null) {
            return;
        }

        $business->branches()->create([
            'delivery_zone_id' => $data['delivery_zone_id'] ?? null,
            'name_ar' => $data['branch_name'] ?: 'الفرع الرئيسي',
            'address' => $data['branch_address'],
            'phone' => $data['branch_phone'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function validatedProducts(Request $request): array
    {
        $products = $request->validate([
            'products' => ['nullable', 'array'],
            'products.*.name_ar' => ['nullable', 'required_with:products.*.price', 'string', 'max:255'],
            'products.*.category_name' => ['nullable', 'string', 'max:255'],
            'products.*.product_category_id' => ['nullable', 'exists:product_categories,id'],
            'products.*.description' => ['nullable', 'string'],
            'products.*.price' => ['nullable', 'required_with:products.*.name_ar', 'numeric', 'min:0'],
            'products.*.is_available' => ['sometimes', 'boolean'],
            'products.*.is_active' => ['sometimes', 'boolean'],
        ])['products'] ?? [];

        return array_values(array_filter(
            $products,
            fn (array $product): bool => filled($product['name_ar'] ?? null),
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withCover(Request $request, array $payload, ?Business $business = null): array
    {
        $request->validate([
            'cover' => CatalogImage::rules(),
        ]);

        if ($request->hasFile('cover')) {
            $payload['cover_path'] = CatalogImage::store($request->file('cover'), 'businesses', $business?->cover_path);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function productInput(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'product_category_id' => $request->filled('product_category_id') ? $request->input('product_category_id') : null,
            'category_name' => $request->filled('category_name') ? $request->input('category_name') : null,
        ]);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => CatalogImage::rules(),
        ]);

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
     * @param  list<array<string, mixed>>  $products
     */
    private function applyProducts(Business $business, array $products): void
    {
        foreach ($products as $product) {
            $business->products()->create($this->productAttributes($business, $product));
        }
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function productAttributes(Business $business, array $product): array
    {
        $categoryId = $product['product_category_id'] ?? null;

        if (filled($product['category_name'] ?? null)) {
            $categoryId = ProductCategory::query()->firstOrCreate(
                [
                    'business_id' => $business->id,
                    'name_ar' => $product['category_name'],
                ],
                ['is_active' => true],
            )->id;
        }

        if (blank($categoryId)) {
            $categoryId = ProductCategory::query()->firstOrCreate(
                [
                    'business_id' => $business->id,
                    'name_ar' => 'عام',
                ],
                ['is_active' => true],
            )->id;
        }

        $category = ProductCategory::query()->findOrFail($categoryId);

        abort_unless($category->business_id === $business->id, 422);

        $attributes = [
            'product_category_id' => $category->id,
            'name_ar' => $product['name_ar'],
            'description' => $product['description'] ?? null,
            'price' => $product['price'],
            'is_available' => (bool) ($product['is_available'] ?? true),
            'is_active' => (bool) ($product['is_active'] ?? true),
        ];

        if (array_key_exists('image_path', $product)) {
            $attributes['image_path'] = $product['image_path'];
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryInput(Request $request): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function canManageCatalog(Request $request, Business $business): bool
    {
        $user = $request->user();

        return $user->can('update', $business)
            && ($user->hasRole('super_admin') || $user->can('products.manage') || $user->can('businesses.manage'));
    }

    private function authorizeCatalog(Request $request, Business $business): void
    {
        abort_unless($this->canManageCatalog($request, $business), 403);
    }
}
