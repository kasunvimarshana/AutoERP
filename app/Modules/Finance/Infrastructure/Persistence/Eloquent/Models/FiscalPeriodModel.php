<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class FiscalPeriodModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope;

    protected $table = 'fiscal_periods';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'end_date' => 'date',
            'metadata' => 'array',
            'row_version' => 'integer',
            'start_date' => 'date',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYearModel::class, 'fiscal_year_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntryModel::class, 'fiscal_period_id');
    }

}
