<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\VehicleRental\Enums\RentalDepositLinkStatus;
use Modules\VehicleRental\Enums\RentalDepositLinkType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalDepositLink extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_deposit_links';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'deposit_requirement_id' => 'integer',
            'link_type' => RentalDepositLinkType::class,
            'payment_id' => 'integer',
            'invoice_id' => 'integer',
            'amount' => 'decimal:6',
            'status' => RentalDepositLinkStatus::class,
            'linked_at' => 'datetime',
            'reverses_link_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(RentalDepositRequirement::class, 'deposit_requirement_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function reversesLink(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_link_id');
    }
}
