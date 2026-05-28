<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerTaxProfileModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customer_tax_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'tax_group_id' => 'integer',
            'tax_exempt' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
