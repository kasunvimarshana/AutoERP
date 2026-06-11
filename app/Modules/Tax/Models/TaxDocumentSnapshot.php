<?php

declare(strict_types=1);

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class TaxDocumentSnapshot extends CoreModel
{
    protected $table = 'tax_document_snapshots';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'source_id' => 'integer',
            'line_id' => 'integer',
            'tax_id' => 'integer',
            'rate' => 'decimal:6',
            'sequence' => 'integer',
            'taxable_amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'is_withholding' => 'boolean',
            'recoverable' => 'boolean',
            'payable' => 'boolean',
            'receivable' => 'boolean',
            'posted' => 'boolean',
            'source_date' => 'date',
            'metadata' => 'array',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
