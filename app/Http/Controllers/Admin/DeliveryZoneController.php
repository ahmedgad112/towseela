<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryZoneController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        return Inertia::render('Admin/Zones/Index', [
            'zones' => DeliveryZone::query()->with('feeRules')->orderBy('governorate')->orderBy('name_ar')->orderBy('id')->get(),
            'governorates' => $this->governorateNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'governorate' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zone = DeliveryZone::query()->create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'governorate' => $data['governorate'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('fee')) {
            $zone->feeRules()->create([
                'min_order' => $data['min_order'] ?? 0,
                'fee' => $data['fee'],
                'is_active' => true,
            ]);
        }

        return back()->with('success', __('تم حفظ المنطقة.'));
    }

    public function storeRule(Request $request, DeliveryZone $deliveryZone): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $deliveryZone->feeRules()->create($request->validate([
            'min_order' => ['required', 'numeric', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return back()->with('success', __('تم حفظ قاعدة الرسوم.'));
    }

    public function updateRule(Request $request, DeliveryFeeRule $deliveryFeeRule): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $deliveryFeeRule->update($request->validate([
            'min_order' => ['required', 'numeric', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return back()->with('success', __('تم تحديث الرسوم.'));
    }

    /**
     * @return list<string>
     */
    private function governorateNames(): array
    {
        return collect(config('towseela.governorates'))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }
}
