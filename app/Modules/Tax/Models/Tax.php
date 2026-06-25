<?php

declare(strict_types=1);

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class Tax extends CoreModel
{
    protected $table = 'taxes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_withholding' => 'boolean',
            'recoverable' => 'boolean',
            'payable' => 'boolean',
            'receivable' => 'boolean',
            'active' => 'boolean',
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

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class, 'tax_id');
    }

    public function groupLines(): HasMany
    {
        return $this->hasMany(TaxGroupLine::class, 'tax_id');
    }

    public function postingProfiles(): HasMany
    {
        return $this->hasMany(TaxPostingProfile::class, 'tax_id');
    }
}
