<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'customer_name' => $this->customer_name,
            'contact_details' => $this->buildContactDetails(),
            'business_information' => array_filter([
                'tax_identification' => $this->tax_id,
                'website' => $this->website,
            ]),
            'customer_tier' => $this->customer_tier,
            'payment_configuration' => [
                'payment_terms' => $this->payment_terms,
                'payment_days' => $this->payment_term_days,
                'preferred_currency' => $this->preferred_currency,
            ],
            'credit_management' => [
                'credit_limit' => $this->formatCurrency($this->credit_limit),
                'outstanding_balance' => $this->formatCurrency($this->outstanding_balance),
                'available_credit' => $this->formatCurrency($this->credit_limit - $this->outstanding_balance),
                'credit_utilization_percent' => $this->calculateCreditUtilization(),
            ],
            'addresses' => [
                'billing' => $this->buildBillingAddress(),
                'shipping' => $this->buildShippingAddress(),
            ],
            'account_status' => $this->is_active ? 'active' : 'inactive',
            'additional_notes' => $this->notes,
            'custom_attributes' => $this->custom_fields ?? [],
            'sales_orders' => $this->when(
                $this->relationLoaded('salesOrders'),
                fn() => [
                    'total_orders' => $this->salesOrders->count(),
                    'total_value' => $this->formatCurrency($this->salesOrders->sum('total_amount')),
                ]
            ),
            'record_dates' => [
                'customer_since' => $this->created_at?->toDateString(),
                'last_updated' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function buildContactDetails(): array
    {
        return array_filter([
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'fax' => $this->fax,
        ]);
    }

    private function buildBillingAddress(): ?array
    {
        if (!$this->billing_address_line1 && !$this->billing_city) {
            return null;
        }

        return array_filter([
            'address_line1' => $this->billing_address_line1,
            'address_line2' => $this->billing_address_line2,
            'city' => $this->billing_city,
            'state' => $this->billing_state,
            'country' => $this->billing_country,
            'postal_code' => $this->billing_postal_code,
            'formatted' => $this->formatAddress('billing'),
        ]);
    }

    private function buildShippingAddress(): ?array
    {
        if (!$this->shipping_address_line1 && !$this->shipping_city) {
            return null;
        }

        return array_filter([
            'address_line1' => $this->shipping_address_line1,
            'address_line2' => $this->shipping_address_line2,
            'city' => $this->shipping_city,
            'state' => $this->shipping_state,
            'country' => $this->shipping_country,
            'postal_code' => $this->shipping_postal_code,
            'formatted' => $this->formatAddress('shipping'),
        ]);
    }

    private function formatAddress(string $type): string
    {
        $parts = [];
        
        if ($type === 'billing') {
            $parts = array_filter([
                $this->billing_address_line1,
                $this->billing_address_line2,
                $this->billing_city,
                $this->billing_state,
                $this->billing_postal_code,
                $this->billing_country,
            ]);
        } else {
            $parts = array_filter([
                $this->shipping_address_line1,
                $this->shipping_address_line2,
                $this->shipping_city,
                $this->shipping_state,
                $this->shipping_postal_code,
                $this->shipping_country,
            ]);
        }

        return implode(', ', $parts);
    }

    private function calculateCreditUtilization(): float
    {
        if ($this->credit_limit <= 0) {
            return 0;
        }

        return round(($this->outstanding_balance / $this->credit_limit) * 100, 2);
    }

    private function formatCurrency($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
