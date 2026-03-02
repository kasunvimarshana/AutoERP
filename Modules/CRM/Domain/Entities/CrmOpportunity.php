<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CrmOpportunity entity.
 *
 * Represents a qualified sales opportunity derived from a lead.
 * expected_revenue and probability are cast to string for BCMath precision.
 */
class CrmOpportunity extends Model
{
    use HasTenant;

    protected $table = 'crm_opportunities';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'pipeline_stage_id',
        'title',
        'expected_revenue',
        'close_date',
        'status',
        'assigned_to',
        'probability',
        'notes',
    ];

    protected $casts = [
        'lead_id'           => 'integer',
        'pipeline_stage_id' => 'integer',
        'expected_revenue'  => 'string',
        'close_date'        => 'date',
        'assigned_to'       => 'integer',
        'probability'       => 'string',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'pipeline_stage_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'opportunity_id');
    }
}
