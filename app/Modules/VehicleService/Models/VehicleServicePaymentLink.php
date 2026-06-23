<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;

final class VehicleServicePaymentLink extends TenantOwnedModel
{
    protected $table = 'vehicle_service_payment_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'payment_id' => 'integer',
            'invoice_id' => 'integer',
            'allocated_amount' => 'decimal:6',
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
