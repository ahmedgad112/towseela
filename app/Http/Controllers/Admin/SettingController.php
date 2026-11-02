<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(Request $request, PlatformSettings $settings): Response
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        return Inertia::render('Admin/Settings', [
            'settings' => $settings->all(),
        ]);
    }

    public function update(Request $request, PlatformSettings $settings, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $data = $request->validate([
            'brand.name_ar' => ['required', 'string', 'max:80'],
            'brand.name_en' => ['required', 'string', 'max:80'],
            'brand.tagline' => ['nullable', 'string', 'max:120'],
            'brand.support_phone' => ['required', 'string', 'max:20'],
            'brand.support_email' => ['nullable', 'email', 'max:255'],
            'brand.whatsapp' => ['nullable', 'string', 'max:20'],
            'operations.currency' => ['required', 'string', 'max:10'],
            'operations.currency_label' => ['required', 'string', 'max:20'],
            'operations.allow_mixed_business_cart' => ['required', 'boolean'],
            'operations.require_proof_of_delivery_note' => ['required', 'boolean'],
            'operations.driver_active_order_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'operations.overdue_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'map.default_lat' => ['required', 'numeric', 'between:-90,90'],
            'map.default_lng' => ['required', 'numeric', 'between:-180,180'],
            'map.default_city' => ['required', 'string', 'max:80'],
            'features.customer_registration_enabled' => ['required', 'boolean'],
            'features.maintenance_mode' => ['required', 'boolean'],
            'features.maintenance_message' => [
                Rule::requiredIf(fn () => $request->boolean('features.maintenance_mode')),
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $settings->update(
            $data['brand'],
            [
                ...$data['operations'],
                'allow_mixed_business_cart' => $request->boolean('operations.allow_mixed_business_cart'),
                'require_proof_of_delivery_note' => $request->boolean('operations.require_proof_of_delivery_note'),
                'driver_active_order_limit' => (int) $data['operations']['driver_active_order_limit'],
                'overdue_minutes' => (int) $data['operations']['overdue_minutes'],
            ],
            [
                ...$data['map'],
                'default_lat' => (float) $data['map']['default_lat'],
                'default_lng' => (float) $data['map']['default_lng'],
            ],
            [
                'customer_registration_enabled' => $request->boolean('features.customer_registration_enabled'),
                'maintenance_mode' => $request->boolean('features.maintenance_mode'),
                'maintenance_message' => $data['features']['maintenance_message'] ?? '',
            ],
        );

        $audit->record(AuditAction::SettingsUpdated, $request->user(), $request->user(), [
            'sections' => ['brand', 'operations', 'map', 'features'],
        ]);

        return back()->with('success', __('تم حفظ إعدادات المنصة.'));
    }
}
