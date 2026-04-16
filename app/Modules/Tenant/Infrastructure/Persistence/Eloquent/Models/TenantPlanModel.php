<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TenantPlanModel extends Model
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

    public function tenants()
    {
        return $this->hasMany(TenantModel::class, 'tenant_plan_id');
    }
}
