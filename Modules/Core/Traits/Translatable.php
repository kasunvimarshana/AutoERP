<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * Translatable Trait
 *
 * Provides multi-language support for model attributes using native Laravel features.
 * Stores translations as JSON in the database without external dependencies.
 *
 * Usage:
 * 1. Add trait to your model: use Translatable;
 * 2. Define translatable attributes: protected $translatable = ['name', 'description'];
 * 3. Cast translatable attributes to array in your model
 *
 * Migration example:
 * $table->json('name')->nullable();
 *
 * Example:
 * $product->setTranslation('name', 'en', 'Product Name');
 * $product->setTranslation('name', 'es', 'Nombre del Producto');
 * $name = $product->getTranslation('name', 'es');
 * $name = $product->getTranslation('name'); // Uses current app locale
 */
trait Translatable
{
    /**
     * Get the list of translatable attributes.
     *
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Get a translation for a specific attribute and locale.
     */
    public function getTranslation(string $attribute, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if (! $this->isTranslatableAttribute($attribute)) {
            return $this->getAttribute($attribute);
        }

        $translations = $this->getTranslations($attribute);

        return $translations[$locale] ?? $translations[config('app.fallback_locale')] ?? null;
    }

    /**
     * Get all translations for a specific attribute.
     *
     * @return array<string, string>
     */
    public function getTranslations(string $attribute): array
    {
        if (! $this->isTranslatableAttribute($attribute)) {
            return [$this->getAttribute($attribute)];
        }

        $value = $this->getAttributeValue($attribute);

        return is_array($value) ? $value : [];
    }

    /**
     * Set a translation for a specific attribute and locale.
     */
    public function setTranslation(string $attribute, string $locale, string $value): self
    {
        if (! $this->isTranslatableAttribute($attribute)) {
            $this->setAttribute($attribute, $value);

            return $this;
        }

        $translations = $this->getTranslations($attribute);
        $translations[$locale] = $value;

        $this->setAttribute($attribute, $translations);

        return $this;
    }

    /**
     * Check if an attribute is translatable.
     */
    public function isTranslatableAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->getTranslatableAttributes());
    }

    /**
     * Get attribute value with automatic translation.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttribute($key);
        }

        return $this->getTranslation($key);
    }
}
