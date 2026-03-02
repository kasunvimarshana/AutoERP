<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * IntegrationLog entity.
 *
 * Immutable record of every inbound and outbound integration event.
 */
class IntegrationLog extends Model
{
    use HasTenant;

    protected $table = 'integration_logs';

    protected $fillable = [
        'tenant_id',
        'integration_type',
        'direction',
        'event_name',
        'payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
