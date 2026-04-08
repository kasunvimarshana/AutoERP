<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OrderModel — pure persistence model for the orders table.
 *
 * No business logic here. The domain entity Order is the source of truth
 * for all behaviour. This model only handles ORM mechanics.
 */
class OrderModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table      = 'orders';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'status',
        'total_amount_cents',
        'total_currency',
        'placed_at',
    ];

    protected $casts = [
        'placed_at'          => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
        'total_amount_cents' => 'integer',
    ];
}
