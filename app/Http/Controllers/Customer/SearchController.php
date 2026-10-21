<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $term = trim((string) $request->string('q'));
        $categoryId = $request->integer('category');

        $businesses = Business::query()
            ->active()
            ->with('category')
            ->when($categoryId, fn ($query) => $query->where('business_category_id', $categoryId))
            ->when($term !== '', fn ($query) => $query->where(function ($builder) use ($term) {
                $builder->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            }))
            ->orderBy('name_ar')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $products = Product::query()
            ->available()
            ->with('business')
            ->when($term !== '', fn ($query) => $query->where(function ($builder) use ($term) {
                $builder->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%");
            }))
            ->orderBy('name_ar')
            ->orderBy('id')
            ->limit(12)
            ->get();

        return Inertia::render('Customer/Search', [
            'filters' => ['q' => $term, 'category' => $categoryId ?: null],
            'categories' => BusinessCategory::query()->active()->orderBy('sort_order')->get(),
            'businesses' => $businesses,
            'products' => $products,
        ]);
    }
}
