<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BusinessCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => BusinessCategory::query()->withCount('businesses')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        BusinessCategory::query()->create([
            ...$data,
            'slug' => Str::slug($data['name_en'] ?? $data['name_ar']).'-'.Str::lower(Str::random(4)),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', __('تم حفظ التصنيف.'));
    }

    public function update(Request $request, BusinessCategory $businessCategory): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $businessCategory->update($request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return back()->with('success', __('تم تحديث التصنيف.'));
    }
}
