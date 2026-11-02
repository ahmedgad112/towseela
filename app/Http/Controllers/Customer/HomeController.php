<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Customer/Home', [
            'categories' => BusinessCategory::query()->active()->orderBy('sort_order')->orderBy('id')->get(),
            'businesses' => Business::query()
                ->active()
                ->with('category:id,name_ar,slug,icon')
                ->withCount(['products as available_products_count' => fn ($query) => $query->available()])
                ->orderBy('name_ar')
                ->orderBy('id')
                ->limit(48)
                ->get(['id', 'business_category_id', 'name_ar', 'slug', 'description', 'cover_path', 'min_order_amount', 'delivery_time_min', 'delivery_time_max']),
        ]);
    }
}
