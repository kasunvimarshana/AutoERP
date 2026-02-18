<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasContacts Trait
 *
 * Provides polymorphic contact relationship support.
 * Allows entities to have multiple contacts (phone, email, etc.)
 * using polymorphic relationships.
 *
 * Usage:
 * 1. Add trait to your model: use HasContacts;
 * 2. Create contacts table with morphable columns
 * 3. Access via $model->contacts relationship
 *
 * Migration example:
 * $table->morphs('contactable');
 */
trait HasContacts
{
    /**
     * Get all contacts for the model.
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(config('core.models.contact', 'App\Models\Contact'), 'contactable');
    }

    /**
     * Get primary email contact.
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        $contact = $this->contacts()->where('type', 'email')->where('is_primary', true)->first();

        return $contact?->value;
    }

    /**
     * Get primary phone contact.
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        $contact = $this->contacts()->where('type', 'phone')->where('is_primary', true)->first();

        return $contact?->value;
    }
}
