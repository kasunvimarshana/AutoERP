<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasActiveScope;
use App\Support\Eloquent\Concerns\HasReferenceScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;

class TenantPlanModel extends Model
{
    use HasActiveScope, HasReferenceScope, SoftDeletes;

    protected $table = 'tenant_plans';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'name';

    protected function casts(): array
    {
        return [
            'currency_id' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'limits' => 'array',
            'metadata' => 'array',
            'price' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(TenantModel::class, 'tenant_plan_id');
    }
}
