<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Expense Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $category_id
 * @property string $reference_number
 * @property \Carbon\Carbon $expense_date
 * @property float $amount
 * @property string|null $payment_method_id
 * @property string|null $contact_id
 * @property string|null $notes
 * @property string|null $document_path
 * @property string $created_by
 */
class Expense extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_expenses';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'category_id',
        'reference_number',
        'expense_date',
        'amount',
        'payment_method_id',
        'contact_id',
        'notes',
        'document_path',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
