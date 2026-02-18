<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\BusinessLocation;

class BusinessLocationService
{
    public function create(array $data): BusinessLocation
    {
        return BusinessLocation::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'invoice_scheme_id' => $data['invoice_scheme_id'] ?? null,
            'invoice_layout_id' => $data['invoice_layout_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'settings' => $data['settings'] ?? null,
        ]);
    }

    public function update(BusinessLocation $location, array $data): BusinessLocation
    {
        $location->update([
            'name' => $data['name'] ?? $location->name,
            'code' => $data['code'] ?? $location->code,
            'address' => $data['address'] ?? $location->address,
            'city' => $data['city'] ?? $location->city,
            'state' => $data['state'] ?? $location->state,
            'country' => $data['country'] ?? $location->country,
            'zip_code' => $data['zip_code'] ?? $location->zip_code,
            'phone' => $data['phone'] ?? $location->phone,
            'email' => $data['email'] ?? $location->email,
            'invoice_scheme_id' => $data['invoice_scheme_id'] ?? $location->invoice_scheme_id,
            'invoice_layout_id' => $data['invoice_layout_id'] ?? $location->invoice_layout_id,
            'is_active' => $data['is_active'] ?? $location->is_active,
            'settings' => $data['settings'] ?? $location->settings,
        ]);

        return $location;
    }

    public function delete(BusinessLocation $location): bool
    {
        return $location->delete();
    }

    public function getActive()
    {
        return BusinessLocation::active()->get();
    }
}
