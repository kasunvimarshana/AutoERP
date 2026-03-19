<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Device extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'devices';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'device_id',
        'device_name',
        'device_type',
        'platform',
        'user_agent',
        'ip_address',
        'is_trusted',
        'last_active_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
