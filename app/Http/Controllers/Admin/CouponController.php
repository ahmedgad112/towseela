<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('offers.manage'), 403);

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => Coupon::query()->latest()->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('offers.manage'), 403);

        Coupon::query()->create($this->validated($request));

        return back()->with('success', __('تم إنشاء العرض.'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        abort_unless($request->user()->can('offers.manage'), 403);
        $coupon->update($this->validated($request, $coupon));

        return back()->with('success', __('تم تحديث العرض.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge([
            'usage_limit' => $request->filled('usage_limit') ? $request->input('usage_limit') : null,
            'max_discount' => $request->filled('max_discount') ? $request->input('max_discount') : null,
            'starts_at' => $request->filled('starts_at') ? $request->input('starts_at') : null,
            'ends_at' => $request->filled('ends_at') ? $request->input('ends_at') : null,
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'name_ar' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
