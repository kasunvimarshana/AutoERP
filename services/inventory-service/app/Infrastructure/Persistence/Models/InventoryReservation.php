<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * InventoryReservation Eloquent Model
 *
 * Tracks stock reservations created by the Order Saga.
 */
class InventoryReservation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'inventory_reservations';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'saga_id',
        'status',
        'items',
        'reserved_at',
        'released_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'items'       => 'array',
        'metadata'    => 'array',
        'reserved_at' => 'datetime',
        'released_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];
}
