<?php

declare(strict_types=1);

namespace Modules\Extension\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AttachmentModel extends TenantOwnedModel
{
    protected $table = 'attachments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'attachable_id' => 'integer',
            'source_id' => 'integer',
            'source_context' => 'array',
            'size' => 'integer',
        ]);
    }
}
