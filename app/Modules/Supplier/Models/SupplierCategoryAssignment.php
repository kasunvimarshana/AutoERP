<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class SupplierCategoryAssignment extends CoreModel
{
    protected $table = 'supplier_category_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'supplier_category_id' => 'integer',
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupplierCategory::class, 'supplier_category_id');
    }
}
