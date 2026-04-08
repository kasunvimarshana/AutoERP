<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class AccountModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'sub_type',
        'normal_balance',
        'currency_code',
        'description',
        'is_active',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_system'  => 'boolean',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Parent account in the chart-of-accounts hierarchy.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Journal entry lines posted to this account.
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'account_id');
    }
}
