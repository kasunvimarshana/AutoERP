<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RevokedToken Model
 *
 * Stores revoked JWT tokens
 *
 * @property string $id
 * @property string $token_id
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 */
class RevokedToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'token_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set created_at
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }
}
