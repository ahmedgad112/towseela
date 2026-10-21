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
            'category',
            'hours',
            'productCategories' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id'),
            'productCategories.products' => fn ($query) => $query->available()->with('options.values')->orderBy('sort_order')->orderBy('id'),
        ]);

        return Inertia::render('Customer/Business/Show', [
            'business' => $business,
            'isOpen' => $business->isOpenAt(),
        ]);
    }
}
