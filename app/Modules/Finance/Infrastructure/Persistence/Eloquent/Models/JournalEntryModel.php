<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class JournalEntryModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'journal_entries';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'reference_number',
        'entry_date',
        'description',
        'status',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'total_debit',
        'total_credit',
        'currency',
        'source_type',
        'source_id',
        'metadata',
    ];

    protected $casts = [
        'id'           => 'integer',
        'tenant_id'    => 'integer',
        'posted_by'    => 'integer',
        'voided_by'    => 'integer',
        'source_id'    => 'integer',
        'total_debit'  => 'float',
        'total_credit' => 'float',
        'posted_at'    => 'datetime',
        'voided_at'    => 'datetime',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
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

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'journal_entry_id')
                    ->orderBy('sort_order');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'voided_by');
    }
}
