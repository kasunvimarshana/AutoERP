<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant Configuration Model
 *
 * Stores runtime-modifiable configurations per tenant.
 * Supports database connections, cache drivers, mail settings,
 * message broker configs, etc. without requiring app restart.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $config_key
 * @property string|null $config_value
 * @property string $config_group
 * @property bool $is_encrypted
 */
class TenantConfiguration extends Model
{
    protected $table = 'tenant_configurations';

    protected $fillable = [
        'tenant_id',
        'config_key',
        'config_value',
        'config_group',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the tenant this configuration belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the decoded value (JSON if applicable).
     */
    public function getDecodedValueAttribute(): mixed
    {
        $value = $this->config_value;

        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
