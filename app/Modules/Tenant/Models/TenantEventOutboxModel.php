<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\CoreModel;

final class TenantEventOutboxModel extends CoreModel
{
    use HasImmutableTenantOwnership;

    protected $table = 'tenant_event_outbox';

    protected $fillable = [
        'event_uuid',
        'tenant_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'last_error',
        'available_at',
        'claim_token',
        'claimed_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'payload' => 'array',
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'claimed_at' => 'datetime',
            'published_at' => 'datetime',
        ]);
    }
}
