<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class AccountModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'nature',
        'classification',
        'description',
        'is_active',
        'is_bank_account',
        'is_system',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'currency',
        'opening_balance',
        'current_balance',
        'metadata',
    ];

    protected $casts = [
        'id'              => 'integer',
        'tenant_id'       => 'integer',
        'parent_id'       => 'integer',
        'is_active'       => 'boolean',
        'is_bank_account' => 'boolean',
        'is_system'       => 'boolean',
        'opening_balance' => 'float',
        'current_balance' => 'float',
        'metadata'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'account_id');
    }
}
