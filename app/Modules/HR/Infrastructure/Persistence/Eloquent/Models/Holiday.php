<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class Holiday extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'holidays';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'holiday_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'created_by');
    }
}
