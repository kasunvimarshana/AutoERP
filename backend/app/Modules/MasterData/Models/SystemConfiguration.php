<?php

namespace App\Modules\MasterData\Models;

use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SystemConfiguration extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'tenant_id',
        'module',
        'key',
        'value',
        'type',
        'description',
        'is_system',
        'is_encrypted',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_encrypted' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the configuration.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get configuration value with proper type casting.
     */
    public function getValue(): mixed
    {
        $value = $this->is_encrypted ? Crypt::decryptString($this->value) : $this->value;

        return match($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Set configuration value with proper encryption.
     */
    public function setValue(mixed $value): void
    {
        $stringValue = match($this->type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $this->value = $this->is_encrypted ? Crypt::encryptString($stringValue) : $stringValue;
    }

    /**
     * Static helper to get config value by module and key.
     */
    public static function get(string $module, string $key, mixed $default = null): mixed
    {
        $config = static::forModule($module)->where('key', $key)->first();
        
        return $config ? $config->getValue() : $default;
    }

    /**
     * Static helper to set config value by module and key.
     */
    public static function set(string $module, string $key, mixed $value, array $attributes = []): self
    {
        $config = static::updateOrCreate(
            [
                'tenant_id' => config('app.tenant_id'),
                'module' => $module,
                'key' => $key,
            ],
            array_merge($attributes, [
                'type' => $attributes['type'] ?? 'string',
            ])
        );

        $config->setValue($value);
        $config->save();

        return $config;
    }
}
