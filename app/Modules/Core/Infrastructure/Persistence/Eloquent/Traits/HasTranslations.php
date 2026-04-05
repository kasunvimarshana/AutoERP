<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Make an Eloquent model translatable by storing translations as JSON.
 *
 * Supports simple attributes (`name`) and nested paths (`meta->seo->title`).
 * All translations are stored in a single JSON column per top‑level attribute.
 *
 * Usage:
 *   class Tenant extends Model {
 *       use HasTranslations;
 *       protected $translatable = ['name', 'meta->seo_title'];
 *   }
 */
trait HasTranslations
{
    /**
     * The attributes that are translatable.
     * Example: ['name', 'meta->seo_title', 'data->fields->title']
     *
     * @var array<string>
     */
    protected $translatable = [];

    /**
     * Boot the trait – ensure translatable attributes are fillable and validate on save.
     */
    protected static function bootHasTranslations(): void
    {
        static::retrieved(function (Model $model) {
            $model->mergeFillable($model->getTranslatableAttributes());
        });

        static::saving(function (Model $model) {
            $model->ensureTranslatableAttributesAreValidJson();
        });
    }

    /**
     * Ensure that all translatable columns contain valid JSON.
     *
     * @throws InvalidArgumentException
     */
    protected function ensureTranslatableAttributesAreValidJson(): void
    {
        foreach ($this->getTopLevelTranslatableColumns() as $column) {
            $value = $this->attributes[$column] ?? null;
            if ($value !== null && ! $this->isValidJsonArray($value)) {
                throw new InvalidArgumentException(
                    "Translatable column [{$column}] must be a valid JSON array of translations."
                );
            }
        }
    }

    /**
     * Get the list of translatable attribute paths.
     *
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    /**
     * Get the top‑level JSON columns that contain translations.
     *
     * @return array<string>
     */
    protected function getTopLevelTranslatableColumns(): array
    {
        $columns = [];
        foreach ($this->getTranslatableAttributes() as $path) {
            $columns[] = $this->getColumnName($path);
        }

        return array_unique($columns);
    }

    /**
     * Check if an attribute path is translatable.
     */
    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->getTranslatableAttributes(), true);
    }

    /**
     * Get a translation for a given attribute path and locale.
     *
     * @param  string  $key  The attribute path (e.g., 'name' or 'meta->seo_title')
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true)
    {
        $this->ensureIsTranslatable($key);

        $translations = $this->getTranslations($key);
        $value = $translations[$locale] ?? null;

        if ($useFallbackLocale && is_null($value) && $this->getFallbackLocale() !== $locale) {
            $value = $translations[$this->getFallbackLocale()] ?? null;
        }

        return $value;
    }

    /**
     * Set a translation for a given attribute path and locale.
     *
     * @param  string  $key  The attribute path (e.g., 'name' or 'meta->seo_title')
     * @param  mixed  $value
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setTranslation(string $key, string $locale, $value): self
    {
        $this->ensureIsTranslatable($key);

        $column = $this->getColumnName($key);
        $path = $this->getNestedPath($key);

        $current = $this->getTranslations($column);
        $target = &$current;

        foreach ($path as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target[$locale] = $value;

        $this->attributes[$column] = $this->encodeTranslations($current);

        return $this;
    }

    /**
     * Get all translations for a given attribute path.
     *
     * @param  string  $key  The attribute path (e.g., 'name' or 'meta->seo_title')
     *
     * @throws InvalidArgumentException
     */
    public function getTranslations(string $key): array
    {
        $this->ensureIsTranslatable($key);

        $column = $this->getColumnName($key);
        $path = $this->getNestedPath($key);

        $value = $this->getAttributeValue($column);

        if (is_string($value) && $this->isJson($value)) {
            $value = json_decode($value, true);
        }

        $translations = is_array($value) ? $value : [];

        foreach ($path as $segment) {
            if (! isset($translations[$segment]) || ! is_array($translations[$segment])) {
                return [];
            }
            $translations = $translations[$segment];
        }

        return is_array($translations) ? $translations : [];
    }

    /**
     * Set all translations for a given attribute path (overwrites existing).
     *
     * @param  array  $translations  ['en' => 'value', 'fr' => 'value']
     * @return $this
     */
    public function setTranslations(string $key, array $translations): self
    {
        $this->ensureIsTranslatable($key);

        $column = $this->getColumnName($key);
        $path = $this->getNestedPath($key);

        $current = $this->getTranslations($column);
        $target = &$current;

        foreach ($path as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target = $translations;

        $this->attributes[$column] = $this->encodeTranslations($current);

        return $this;
    }

    /**
     * Forget one or all translations for an attribute path.
     *
     * @param  string|null  $locale  If null, all translations for this path are forgotten.
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function forgetTranslation(string $key, ?string $locale = null): self
    {
        $this->ensureIsTranslatable($key);

        $column = $this->getColumnName($key);
        $path = $this->getNestedPath($key);

        $current = $this->getTranslations($column);
        $target = &$current;

        $lastSegment = array_pop($path);
        foreach ($path as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                return $this; // path doesn't exist
            }
            $target = &$target[$segment];
        }

        if ($locale) {
            // Remove only one locale
            unset($target[$lastSegment][$locale]);
            if (empty($target[$lastSegment])) {
                unset($target[$lastSegment]);
            }
        } else {
            // Remove the entire nested path
            unset($target[$lastSegment]);
        }

        $this->attributes[$column] = $this->encodeTranslations($current);

        return $this;
    }

    /**
     * Intercept attribute access to return the translation for the current locale.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->isTranslatableAttribute($key)) {
            return $this->getTranslation($key, $this->getLocale());
        }

        return parent::__get($key);
    }

    /**
     * Intercept attribute setting to allow direct assignment of translations.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if ($this->isTranslatableAttribute($key) && is_array($value)) {
            // If an array is passed, treat it as a set of translations for the whole path.
            $this->setTranslations($key, $value);

            return;
        }

        parent::setAttribute($key, $value);
    }

    // -----------  QUERY SCOPES  -----------

    /**
     * Scope a query to only include records where the translation for a given locale matches a value.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function scopeWhereLocale($query, string $key, string $locale, $value, string $operator = '=')
    {
        $this->ensureIsTranslatable($key);

        return $query->where($this->getJsonPath($key, $locale), $operator, $value);
    }

    /**
     * Scope a query to only include records where the translation for one of several locales matches a value.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function scopeWhereLocales($query, string $key, array $locales, $value, string $operator = '=')
    {
        $this->ensureIsTranslatable($key);

        return $query->where(function ($q) use ($key, $locales, $value, $operator) {
            foreach ($locales as $locale) {
                $q->orWhere($this->getJsonPath($key, $locale), $operator, $value);
            }
        });
    }

    /**
     * Scope a query to only include records where the JSON column for a locale contains a given value.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function scopeWhereJsonContainsLocale($query, string $key, string $locale, $value, string $operand = '=')
    {
        $this->ensureIsTranslatable($key);

        return $query->where($this->getJsonPath($key, $locale), $operand, $value);
    }

    /**
     * Scope a query to only include records where the JSON column for any of the locales contains a given value.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function scopeWhereJsonContainsLocales($query, string $key, array $locales, $value, string $operand = '=')
    {
        $this->ensureIsTranslatable($key);

        return $query->where(function ($q) use ($key, $locales, $value, $operand) {
            foreach ($locales as $locale) {
                $q->orWhere($this->getJsonPath($key, $locale), $operand, $value);
            }
        });
    }

    // -----------  PROTECTED UTILITIES (override for customization) -----------

    /**
     * Get the current application locale.
     */
    protected function getLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Get the fallback locale from configuration.
     */
    protected function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }

    /**
     * Encode an array of translations to JSON.
     */
    protected function encodeTranslations(array $translations): string
    {
        return json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Determine if a string is valid JSON.
     */
    protected function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Determine if a value is a valid JSON array (object) of translations.
     *
     * @param  mixed  $value
     */
    protected function isValidJsonArray($value): bool
    {
        if (! is_string($value) || ! $this->isJson($value)) {
            return false;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded);
    }

    /**
     * Ensure an attribute path is translatable.
     *
     *
     * @throws InvalidArgumentException
     */
    protected function ensureIsTranslatable(string $key): void
    {
        if (! $this->isTranslatableAttribute($key)) {
            throw new InvalidArgumentException("Attribute path [{$key}] is not translatable.");
        }
    }

    /**
     * Extract the top‑level column name from a dotted path.
     */
    protected function getColumnName(string $key): string
    {
        $parts = explode('->', $key);

        return $parts[0];
    }

    /**
     * Extract the nested path (without the column) as an array of segments.
     */
    protected function getNestedPath(string $key): array
    {
        $parts = explode('->', $key);
        array_shift($parts); // remove the column name

        return $parts;
    }

    /**
     * Build a JSON path for a given attribute and locale.
     *
     * Example: 'name->en' (for MySQL) – override for other databases.
     */
    protected function getJsonPath(string $key, string $locale): string
    {
        $column = $this->getColumnName($key);
        $path = $this->getNestedPath($key);
        $path[] = $locale; // add the locale as the final segment

        return $column.'->'.implode('->', $path);
    }
}
