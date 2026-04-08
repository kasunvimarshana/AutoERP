<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class TransactionModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'journal_entry_id',
        'reference_number',
        'type',
        'status',
        'transaction_date',
        'amount',
        'currency',
        'exchange_rate',
        'from_account_id',
        'to_account_id',
        'description',
        'category',
        'tags',
        'contact_type',
        'contact_id',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'id'               => 'integer',
        'tenant_id'        => 'integer',
        'journal_entry_id' => 'integer',
        'from_account_id'  => 'integer',
        'to_account_id'    => 'integer',
        'contact_id'       => 'integer',
        'amount'           => 'float',
        'exchange_rate'    => 'float',
        'tags'             => 'array',
        'attachments'      => 'array',
        'metadata'         => 'array',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'to_account_id');
    }
}
