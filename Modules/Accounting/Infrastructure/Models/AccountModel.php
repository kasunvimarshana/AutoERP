<?php

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class AccountModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'accounting_accounts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'is_active',
        'balance',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLineModel::class, 'account_id');
    }
}
