<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalPaymentLink extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_payment_links';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'payment_id' => 'integer',
            'invoice_id' => 'integer',
            'amount' => 'decimal:6',
        ];
    }

    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class, 'payment_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'invoice_id'); }
}
