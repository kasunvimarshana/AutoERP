<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleServicePartUsageModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleServiceTaskModel;

class VehicleJobCardModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'vehicle_job_cards';

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'customer_id',
        'assigned_mechanic_id',
        'job_card_no',
        'workflow_status',
        'service_type',
        'scheduled_at',
        'started_at',
        'completed_at',
        'labor_cost_total',
        'parts_cost_total',
        'subtotal',
        'tax_amount',
        'grand_total',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'vehicle_id' => 'integer',
        'customer_id' => 'integer',
        'assigned_mechanic_id' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'labor_cost_total' => 'decimal:6',
        'parts_cost_total' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(VehicleServiceTaskModel::class, 'job_card_id');
    }

    public function partUsages(): HasMany
    {
        return $this->hasMany(VehicleServicePartUsageModel::class, 'job_card_id');
    }
}
