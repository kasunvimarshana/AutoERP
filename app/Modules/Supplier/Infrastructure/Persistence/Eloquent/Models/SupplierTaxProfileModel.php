<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierTaxProfileModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'supplier_tax_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'withholding_rate' => 'decimal:4',
            'is_tax_exempt' => 'boolean',
            'tax_exempt_until' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
