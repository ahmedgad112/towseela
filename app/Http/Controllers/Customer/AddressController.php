<?php

namespace App\Http\Controllers\Customer;

use App\Enums\AddressLabel;
use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CustomerAddress::class);

        return Inertia::render('Customer/Addresses', [
            'addresses' => $request->user()->addresses()->with('deliveryZone')->latest('is_default')->orderBy('id')->get(),
            'zones' => DeliveryZone::query()->active()->orderBy('governorate')->orderBy('name_ar')->orderBy('id')->get(),
            'governorates' => collect(config('towseela.governorates'))->pluck('name')->values(),
            'labels' => collect(AddressLabel::cases())->map(fn (AddressLabel $label) => [
                'value' => $label->value,
                'label' => $label->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CustomerAddress::class);

        $data = $this->validated($request);
        $address = $request->user()->addresses()->create($data);
        $this->syncDefault($request, $address);

        return back()->with('success', __('تم حفظ العنوان.'));
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $address->update($this->validated($request, $address));
        $this->syncDefault($request, $address);

        return back()->with('success', __('تم تحديث العنوان.'));
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorize('delete', $address);
        $address->delete();

        return back()->with('success', __('تم حذف العنوان.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CustomerAddress $address = null): array
    {
        return $request->validate([
            'label' => ['required', Rule::enum(AddressLabel::class)],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'delivery_zone_id' => ['required', 'exists:delivery_zones,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

    }

    private function syncDefault(Request $request, CustomerAddress $address): void
    {
        if (! $request->boolean('is_default') && $request->user()->addresses()->where('is_default', true)->exists()) {
            return;
        }

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }
}
