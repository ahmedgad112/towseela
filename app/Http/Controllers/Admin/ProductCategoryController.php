<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);

        ProductCategory::query()->create($request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return back()->with('success', __('تم حفظ تصنيف المنتجات.'));
    }
}
