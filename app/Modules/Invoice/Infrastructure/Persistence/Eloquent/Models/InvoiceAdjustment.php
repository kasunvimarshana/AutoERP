<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\Invoice\Domain\Enums\InvoiceAdjustmentDirection;
use Modules\Invoice\Domain\Enums\InvoiceAdjustmentType;
use Modules\Invoice\Domain\Enums\InvoiceCalculationMethod;

final class InvoiceAdjustment extends CoreModel
{
    protected $table = 'invoice_adjustments';

    protected $fillable = [
        'invoice_id',
        'direction',
        'adjustment_type',
        'code',
        'name',
        'calculation_method',
        'rate',
        'base_amount',
        'amount',
        'sort_order',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'invoice_id' => 'integer',
            'direction' => InvoiceAdjustmentDirection::class,
            'adjustment_type' => InvoiceAdjustmentType::class,
            'calculation_method' => InvoiceCalculationMethod::class,
            'rate' => 'decimal:6',
            'base_amount' => 'decimal:4',
            'amount' => 'decimal:4',
            'sort_order' => 'integer',
        ]);
    }
}
