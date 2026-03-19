<?php

declare(strict_types=1);

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceToken extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'service_tokens';

    protected $fillable = [
        'service_name',
        'client_id',
        'client_secret',
        'allowed_scopes',
        'allowed_ips',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'allowed_scopes' => 'array',
        'allowed_ips'    => 'array',
        'is_active'      => 'boolean',
        'last_used_at'   => 'datetime',
        'expires_at'     => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
