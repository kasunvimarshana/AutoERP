<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * InventoryLedger - Immutable record of every stock movement.
 * Essential for traceability, audit trails, and stock history reconstruction.
 */
class InventoryLedger extends Model
{
    // Disable direct updates to maintain immutability
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model to prevent any updates or deletions.
     * Only allow inserts.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::updating(function ($model) {
            throw new \Exception("Inventory Ledger records are immutable and cannot be updated.");
        });

        static::deleting(function ($model) {
            throw new \Exception("Inventory Ledger records are immutable and cannot be deleted.");
        });
    }
}
