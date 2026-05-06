<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property array<string, mixed>|null $features
 * @property array<string, mixed>|null $limits
 * @property string $price
 * @property string $currency_code
 * @property string $billing_interval
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantPlanModel extends BaseModel
{
    use HasAudit;

    protected $table = 'tenant_plans';

    protected $fillable = [
        'name',
        'slug',
        'features',
        'limits',
        'price',
        'currency_code',
        'billing_interval',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(TenantModel::class, 'tenant_plan_id');
    }
}
