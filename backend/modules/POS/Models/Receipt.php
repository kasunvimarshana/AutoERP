<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Receipt Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $transaction_id
 * @property string $format
 * @property string $content
 * @property \Carbon\Carbon|null $printed_at
 * @property int $print_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Receipt extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_receipts';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'format',
        'content',
        'printed_at',
        'print_count',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'print_count' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
