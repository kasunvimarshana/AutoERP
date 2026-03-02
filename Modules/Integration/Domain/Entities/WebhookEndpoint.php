<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WebhookEndpoint entity.
 *
 * Represents a registered webhook destination for a tenant.
 */
class WebhookEndpoint extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'events',
        'secret',
        'headers',
        'is_active',
    ];

    protected $casts = [
        'events'    => 'array',
        'headers'   => 'array',
        'is_active' => 'boolean',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_endpoint_id');
    }
}
