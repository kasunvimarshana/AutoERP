<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Modules\Core\Models\TenantOwnedModel;

final class InvoiceSourceAllocationGuard extends TenantOwnedModel
{
    protected $table = 'invoice_source_allocation_guards';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
        ]);
    }
}
