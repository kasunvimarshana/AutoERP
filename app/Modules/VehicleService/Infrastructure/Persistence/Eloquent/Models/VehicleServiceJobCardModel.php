<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleServiceJobCardModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_service_job_cards';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'vehicle_ownership_id' => 'integer',
            'vehicle_owner_id' => 'integer',
            'service_customer_id' => 'integer',
            'linked_customer_id' => 'integer',
            'billing_customer_id' => 'integer',
            'payer_id' => 'integer',
            'party_context' => 'array',
            'exchange_rate' => 'decimal:4',
            'estimated_hours' => 'decimal:4',
            'actual_hours' => 'decimal:4',
            'warranty_eligible' => 'boolean',
            'is_customer_approval_required' => 'boolean',
            'is_customer_approved' => 'boolean',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'non_inventory_item_subtotal' => 'decimal:4',
            'non_inventory_item_tax_total' => 'decimal:4',
            'non_inventory_item_discount_total' => 'decimal:4',
            'labor_item_subtotal' => 'decimal:4',
            'labor_item_tax_total' => 'decimal:4',
            'labor_item_discount_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'charge_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'advance_amount' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'refund_amount' => 'decimal:4',
            'write_off_amount' => 'decimal:4',
            'balance' => 'decimal:4',
        ]);
    }
}
