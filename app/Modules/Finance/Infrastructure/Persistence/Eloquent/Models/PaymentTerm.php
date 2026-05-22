<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes as HasTenantAndOrganizationScopesTrait;

class PaymentTerm extends Model
{
    use HasTenantAndOrganizationScopesTrait;
    use SoftDeletes;

    protected $table = 'payment_terms';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'due_days' => 'integer',
            'discount_days' => 'integer',
            'discount_rate' => 'decimal:4',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }
}
