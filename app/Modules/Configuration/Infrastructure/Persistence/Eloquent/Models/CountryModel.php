<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;

class CountryModel extends Model
{
    use HasReferenceScope, SoftDeletes;

    protected $table = 'countries';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddressModel::class, 'country_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeModel::class, 'country_id');
    }

    public function supplierAddresses(): HasMany
    {
        return $this->hasMany(SupplierAddressModel::class, 'country_id');
    }
}

