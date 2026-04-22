<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class SupplierModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'supplier_code',
        'name',
        'type',
        'org_unit_id',
        'tax_number',
        'registration_number',
        'currency_id',
        'payment_terms_days',
        'ap_account_id',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'org_unit_id' => 'integer',
        'currency_id' => 'integer',
        'payment_terms_days' => 'integer',
        'ap_account_id' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'ap_account_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddressModel::class, 'supplier_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContactModel::class, 'supplier_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SupplierProductModel::class, 'supplier_id');
    }
}
