<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceLine extends CoreModel
{
    protected $table = 'invoice_lines';

    protected $fillable = [
        'invoice_id',
        'item_id',
        'uom_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_total',
        'tax_total',
        'charge_total',
        'line_total',
        'line_order',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceLineAdjustment::class, 'invoice_line_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'invoice_id' => 'integer',
            'item_id' => 'integer',
            'uom_id' => 'integer',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'charge_total' => 'decimal:4',
            'line_total' => 'decimal:4',
            'line_order' => 'integer',
        ]);
    }
}
