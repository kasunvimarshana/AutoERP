<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasAddresses Trait
 *
 * Provides polymorphic address relationship support.
 * Implements the polymorphic pattern analyzed in resources for
 * flexible, reusable address management across entities.
 *
 * Usage:
 * 1. Add trait to your model: use HasAddresses;
 * 2. Create addresses table with morphable columns
 * 3. Access via $model->addresses relationship
 *
 * Migration example:
 * $table->morphs('addressable');
 *
 * This trait allows multiple entities (Customer, Supplier, Employee, etc.)
 * to have addresses without duplicating address tables or logic.
 */
trait HasAddresses
{
    /**
     * Get all addresses for the model.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(config('core.models.address', 'App\Models\Address'), 'addressable');
    }

    /**
     * Get the primary/billing address.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getBillingAddressAttribute()
    {
        return $this->addresses()->where('type', 'billing')->first();
    }

    /**
     * Get the shipping address.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getShippingAddressAttribute()
    {
        return $this->addresses()->where('type', 'shipping')->first();
    }
}
