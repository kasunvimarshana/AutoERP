<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class OrganizationUnitModel extends BaseModel
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'org_units';

    protected $fillable = [
        'tenant_id',
        'type_id',
        'parent_id',
        'manager_user_id',
        'name',
        'code',
        'path',
        'depth',
        'metadata',
        'is_active',
        'description',
        'image_path',
        'default_revenue_account_id',
        'default_expense_account_id',
        'default_asset_account_id',
        'default_liability_account_id',
        'warehouse_id',
        '_lft',
        '_rgt',
        'row_version',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'type_id' => 'integer',
        'parent_id' => 'integer',
        'manager_user_id' => 'integer',
        'default_revenue_account_id' => 'integer',
        'default_expense_account_id' => 'integer',
        'default_asset_account_id' => 'integer',
        'default_liability_account_id' => 'integer',
        'warehouse_id' => 'integer',
        'metadata' => 'array',
        'depth' => 'integer',
        'is_active' => 'boolean',
        '_lft' => 'integer',
        '_rgt' => 'integer',
        'row_version' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitTypeModel::class, 'type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrganizationUnitAttachmentModel::class, 'org_unit_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(
            (string) config('auth.providers.users.model'),
            'manager_user_id'
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function defaultRevenueAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'default_revenue_account_id');
    }

    public function defaultExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'default_expense_account_id');
    }

    public function defaultAssetAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'default_asset_account_id');
    }

    public function defaultLiabilityAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'default_liability_account_id');
    }

    public function organizationUnitUsers(): HasMany
    {
        return $this->hasMany(OrganizationUnitUserModel::class, 'org_unit_id');
    }
}
