<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function show(Business $business): Response
    {
        abort_unless($business->is_active, 404);

        $business->load([
            'category:id,name_ar,slug,icon',
            'hours',
            'productCategories' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id'),
            'productCategories.products' => fn ($query) => $query
                ->available()
                ->withExists('options as has_options')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $isOpen = $business->isOpenAt();

        return Inertia::render('Customer/Business/Show', [
            'business' => [
                'id' => $business->id,
                'slug' => $business->slug,
                'name_ar' => $business->name_ar,
                'description' => $business->description,
                'cover_url' => $business->cover_url,
                'delivery_time_min' => $business->delivery_time_min,
                'delivery_time_max' => $business->delivery_time_max,
                'min_order_amount' => $business->min_order_amount,
                'category' => $business->category,
                'product_categories' => $business->productCategories->map(fn ($category) => [
                    'id' => $category->id,
                    'name_ar' => $category->name_ar,
                    'products' => $category->products->map(fn ($product) => [
                        'id' => $product->id,
                        'name_ar' => $product->name_ar,
                        'description' => $product->description,
                        'image_url' => $product->image_url,
                        'price' => $product->price,
                        'has_options' => (bool) $product->has_options,
                    ])->values(),
                ])->values(),
            ],
            'isOpen' => $isOpen,
        ]);
    }
}
