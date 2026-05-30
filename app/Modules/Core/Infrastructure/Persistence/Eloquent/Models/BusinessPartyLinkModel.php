<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class BusinessPartyLinkModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'business_party_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'source_party_id' => 'integer',
            'target_party_id' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'metadata' => 'array',
            'source_context' => 'array',
        ]);
    }
}
