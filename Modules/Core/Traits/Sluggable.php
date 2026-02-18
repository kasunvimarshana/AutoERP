<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

/**
 * Sluggable Trait
 *
 * Automatically generates URL-friendly slugs from specified attributes.
 * Useful for creating SEO-friendly URLs and readable identifiers.
 *
 * Usage:
 * 1. Add trait to your model: use Sluggable;
 * 2. Define sluggable attribute: protected $sluggable = 'name';
 * 3. Optionally override getSlugSource() for custom logic
 *
 * Migration example:
 * $table->string('slug')->unique();
 *
 * Example:
 * $product->name = 'My Product Name';
 * $product->save();
 * // $product->slug will be 'my-product-name'
 */
trait Sluggable
{
    /**
     * Boot the Sluggable trait for a model.
     */
    protected static function bootSluggable(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model->generateSlug();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty($model->getSlugSourceAttribute())) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    /**
     * Generate a unique slug for the model.
     */
    protected function generateSlug(): string
    {
        $slug = Str::slug($this->getAttribute($this->getSlugSourceAttribute()));
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug)) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists.
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    /**
     * Get the attribute to use as the slug source.
     */
    protected function getSlugSourceAttribute(): string
    {
        return $this->sluggable ?? 'name';
    }
}
