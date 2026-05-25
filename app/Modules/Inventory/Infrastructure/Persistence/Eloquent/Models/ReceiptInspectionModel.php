<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class ReceiptInspectionModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'receipt_inspections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'inspected_at' => 'datetime',
            'inspected_by' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'inspected_by');
    }

    public function putAwayTasks(): HasMany
    {
        return $this->hasMany(PutAwayTaskModel::class, 'receipt_inspection_id');
    }

    public function pickingTasks(): HasMany
    {
        return $this->hasMany(PickingTaskModel::class, 'receipt_inspection_id');
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }
}

