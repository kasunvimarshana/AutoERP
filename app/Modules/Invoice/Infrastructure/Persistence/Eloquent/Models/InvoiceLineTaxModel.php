<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceLineTaxModel extends CoreModel
{
    protected $table = 'invoice_line_taxes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_line_id' => 'integer',
            'tax_rate_id' => 'integer',
            'tax_rate' => 'decimal:4',
            'taxable_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'account_id' => 'integer',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLineModel::class, 'invoice_line_id');
    }
}
