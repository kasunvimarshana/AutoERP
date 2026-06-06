<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;

final class PurchaseDebitNote extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_debit_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'purchase_return_id' => 'integer',
            'debit_note_date' => 'date',
            'status' => PurchaseDebitNoteStatus::class,
            'amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
        ]);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }
}
