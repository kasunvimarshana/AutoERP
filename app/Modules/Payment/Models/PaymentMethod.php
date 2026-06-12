<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Tenant\Models\TenantModel;

final class PaymentMethod extends CoreModel
{
    use SoftDeletes;

    protected $table = 'payment_methods';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'method_type' => PaymentMethodType::class,
            'direction_allowed' => PaymentMethodDirection::class,
            'requires_reference' => 'boolean',
            'requires_bank_account' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
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

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentLine::class, 'payment_method_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'payment_method_id');
    }
}
