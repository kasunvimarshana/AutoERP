<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment Model
 *
 * Represents a payment for an invoice
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $payment_number
 * @property \Carbon\Carbon $payment_date
 * @property float $amount
 * @property string $payment_method
 * @property string $status
 * @property string|null $reference_number
 * @property string|null $notes
 * @property int|null $processed_by
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Payment extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Invoice\Database\Factories\PaymentFactory
    {
        return \Modules\Invoice\Database\Factories\PaymentFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'status',
        'reference_number',
        'notes',
        'processed_by',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the invoice that owns the payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who processed the payment.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber(): string
    {
        do {
            $prefix = 'PMT';
            $timestamp = now()->format('Ymd');
            // Use random_int for better randomness and add microseconds for more entropy
            $random = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $microtime = str_pad((string) (int) (microtime(true) * 10000 % 10000), 4, '0', STR_PAD_LEFT);

            $paymentNumber = "{$prefix}-{$timestamp}-{$random}{$microtime}";

            // Check if the number already exists
            $exists = static::where('payment_number', $paymentNumber)->exists();
        } while ($exists);

        return $paymentNumber;
    }
}
