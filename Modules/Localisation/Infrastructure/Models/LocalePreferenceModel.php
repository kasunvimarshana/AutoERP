<?php

namespace Modules\Localisation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LocalePreferenceModel extends Model
{
    protected $table = 'localisation_preferences';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'tenant_id',
        'locale',
        'timezone',
        'date_format',
        'number_format',
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
