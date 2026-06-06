<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class CustomerCategoryAssignment extends CoreModel
{
    protected $table = 'customer_category_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'customer_category_id' => 'integer',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }
}
