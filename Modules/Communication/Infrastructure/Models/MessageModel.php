<?php

namespace Modules\Communication\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class MessageModel extends Model
{
    use HasTenantScope;

    protected $table = 'communication_messages';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'channel_id',
        'sender_id',
        'body',
        'type',
    ];

    const UPDATED_AT = null;

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
