<?php

namespace Modules\Localisation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class LanguagePackModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'localisation_language_packs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'locale',
        'name',
        'direction',
        'strings',
        'is_active',
    ];

    protected $casts = [
        'strings'   => 'array',
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
}
