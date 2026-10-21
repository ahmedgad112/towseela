<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'support_phone' => PlatformSetting::getValue('brand')['support_phone'] ?? '19900',
                'require_proof_of_delivery_note' => (bool) config('towseela.require_proof_of_delivery_note'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $data = $request->validate([
            'support_phone' => ['required', 'string', 'max:20'],
        ]);

        $brand = PlatformSetting::getValue('brand', []);
        PlatformSetting::setValue('brand', [...$brand, 'support_phone' => $data['support_phone']]);

        return back()->with('success', __('تم حفظ الإعدادات. إعداد إثبات التسليم يُضبط من ملف البيئة وليس من الواجهة.'));
    }
}
