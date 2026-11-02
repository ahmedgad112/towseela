<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryZoneController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        return Inertia::render('Admin/Zones/Index', [
            'zones' => DeliveryZone::query()
                ->with('feeRules')
                ->withCount(['addresses', 'feeRules'])
                ->orderBy('governorate')
                ->orderBy('name_ar')
                ->orderBy('id')
                ->get(),
            'governorates' => $this->governorateNames(),
            'can' => [
                'manage' => $request->user()->can('delivery_zones.manage'),
            ],
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'governorate' => ['required', 'string', 'max:100', Rule::in($this->governorateNames())],
            'is_active' => ['sometimes', 'boolean'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zone = DeliveryZone::query()->create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'governorate' => $data['governorate'],
            'aliases' => [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('fee')) {
            $zone->feeRules()->create([
                'min_order' => $data['min_order'] ?? 0,
                'fee' => $data['fee'],
                'is_active' => true,
            ]);
        }

        $audit->record(AuditAction::DeliveryZoneCreated, $zone, $request->user(), [
            'name_ar' => $zone->name_ar,
            'governorate' => $zone->governorate,
        ]);

        return back()->with('success', __('تم حفظ المنطقة.'));
    }

    public function destroy(Request $request, DeliveryZone $deliveryZone, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $audit->record(AuditAction::DeliveryZoneDeleted, $deliveryZone, $request->user(), [
            'name_ar' => $deliveryZone->name_ar,
            'governorate' => $deliveryZone->governorate,
        ]);

        $deliveryZone->delete();

        return back()->with('success', __('تم حذف المنطقة.'));
    }

    public function storeRule(Request $request, DeliveryZone $deliveryZone, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $rule = $deliveryZone->feeRules()->create($request->validate([
            'min_order' => ['required', 'numeric', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        $audit->record(AuditAction::DeliveryFeeUpdated, $rule, $request->user(), [
            'delivery_zone_id' => $deliveryZone->id,
            'min_order' => $rule->min_order,
            'fee' => $rule->fee,
        ]);

        return back()->with('success', __('تم حفظ قاعدة الرسوم.'));
    }

    public function updateRule(Request $request, DeliveryFeeRule $deliveryFeeRule, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $deliveryFeeRule->update($request->validate([
            'min_order' => ['required', 'numeric', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        $audit->record(AuditAction::DeliveryFeeUpdated, $deliveryFeeRule, $request->user(), [
            'delivery_zone_id' => $deliveryFeeRule->delivery_zone_id,
            'min_order' => $deliveryFeeRule->min_order,
            'fee' => $deliveryFeeRule->fee,
        ]);

        return back()->with('success', __('تم تحديث الرسوم.'));
    }

    public function destroyRule(Request $request, DeliveryFeeRule $deliveryFeeRule, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('delivery_zones.manage'), 403);

        $audit->record(AuditAction::DeliveryFeeDeleted, $deliveryFeeRule, $request->user(), [
            'delivery_zone_id' => $deliveryFeeRule->delivery_zone_id,
            'min_order' => $deliveryFeeRule->min_order,
            'fee' => $deliveryFeeRule->fee,
        ]);

        $deliveryFeeRule->delete();

        return back()->with('success', __('تم حذف قاعدة الرسوم.'));
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
