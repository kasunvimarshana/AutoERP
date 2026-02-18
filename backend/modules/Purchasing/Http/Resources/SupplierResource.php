<?php

namespace Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_code' => $this->code,
            'supplier_name' => $this->name,
            'primary_contact' => [
                'contact_person' => $this->contact_person,
                'email' => $this->email,
                'phone' => $this->phone,
                'mobile' => $this->mobile,
            ],
            'supplier_address' => [
                'street_address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'formatted_address' => $this->getFormattedAddress(),
            ],
            'business_details' => array_filter([
                'tax_identification' => $this->tax_id,
                'payment_terms' => $this->payment_terms,
                'credit_limit' => $this->formatCurrency($this->credit_limit),
                'currency' => $this->currency_code,
            ]),
            'supplier_rating' => [
                'rating_score' => $this->rating,
                'rating_display' => $this->rating ? "{$this->rating}/5" : 'Not Rated',
            ],
            'supplier_status' => $this->status,
            'status_indicators' => [
                'is_active' => $this->status === 'active',
                'is_suspended' => $this->status === 'suspended',
            ],
            'internal_notes' => $this->notes,
            'purchase_history' => $this->when(
                $this->relationLoaded('purchaseOrders'),
                fn() => [
                    'total_orders' => $this->purchaseOrders->count(),
                    'total_purchase_value' => $this->formatCurrency($this->purchaseOrders->sum('total_amount')),
                ]
            ),
            'record_metadata' => [
                'supplier_since' => $this->created_at?->toDateString(),
                'last_updated' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function getFormattedAddress(): ?string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    private function formatCurrency($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
