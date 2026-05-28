<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SalesDocumentLinkModel extends CoreModel
{
    protected $table = 'sales_document_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
            'document_id' => 'integer',
            'document_line_id' => 'integer',
            'linked_quantity' => 'decimal:4',
            'linked_amount' => 'decimal:4',
            'linked_at' => 'datetime',
            'created_by' => 'integer',
        ]);
    }
}
