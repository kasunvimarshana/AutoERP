<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\User\Models\UserModel;

final class FinanceAccountAssignment extends TenantOwnedModel
{
    protected $table = 'finance_account_assignments';

    protected $guarded = ['id', 'tenant_id', 'row_version', 'scope_key', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'account_role_id' => 'integer',
            'account_id' => 'integer',
            'context_id' => 'integer',
            'effective_from' => 'immutable_date',
            'effective_to' => 'immutable_date',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }

    protected static function booted(): void
    {
        static::deleting(static function (): never {
            throw new LogicException('Finance account assignments cannot be deleted. End or deactivate the assignment instead.');
        });
    }

    public function tenant(): BelongsTo { return $this->belongsTo(TenantModel::class, 'tenant_id'); }
    public function organizationUnit(): BelongsTo { return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id'); }
    public function role(): BelongsTo { return $this->belongsTo(FinanceAccountRole::class, 'account_role_id'); }
    public function account(): BelongsTo { return $this->belongsTo(FinanceAccount::class, 'account_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(UserModel::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(UserModel::class, 'updated_by'); }
}
