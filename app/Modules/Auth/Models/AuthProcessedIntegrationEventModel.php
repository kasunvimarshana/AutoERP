<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthProcessedIntegrationEventModel extends TenantOwnedModel
{
    protected $table = 'auth_processed_integration_events';
    protected $fillable = ['tenant_id', 'source_system', 'event_id', 'event_type', 'processed_at'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['processed_at' => 'immutable_datetime']);
    }
}
