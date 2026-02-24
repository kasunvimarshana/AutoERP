<?php

namespace Modules\Integration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ApiKeyModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'integration_api_keys';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'key_hash',
        'key_prefix',
        'scopes',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'scopes'       => 'array',
        'is_active'    => 'boolean',
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
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
}
