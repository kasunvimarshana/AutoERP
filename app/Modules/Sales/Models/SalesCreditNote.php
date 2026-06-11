<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Sales\Enums\SalesCreditNoteStatus;

final class SalesCreditNote extends CoreModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'credit_note_date' => 'date',
            'status' => SalesCreditNoteStatus::class,
            'amount' => 'decimal:6',
            'allocated_amount' => 'decimal:6',
            'remaining_amount' => 'decimal:6',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }
}
