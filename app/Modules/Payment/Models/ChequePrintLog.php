<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\ChequePrintStatus;
use Modules\Tenant\Models\TenantModel;
use Modules\User\Models\UserModel;

final class ChequePrintLog extends CoreModel
{
    protected $table = 'cheque_print_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'payment_line_id' => 'integer',
            'cheque_template_id' => 'integer',
            'printed_by' => 'integer',
            'printed_at' => 'datetime',
            'print_status' => ChequePrintStatus::class,
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function paymentLine(): BelongsTo
    {
        return $this->belongsTo(PaymentLine::class, 'payment_line_id');
    }

    public function chequeTemplate(): BelongsTo
    {
        return $this->belongsTo(ChequeTemplate::class, 'cheque_template_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'printed_by');
    }
}
