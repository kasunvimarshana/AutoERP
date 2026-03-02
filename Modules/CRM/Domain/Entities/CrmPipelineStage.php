<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CrmPipelineStage entity.
 *
 * Represents a stage in the CRM sales pipeline.
 * win_probability is cast to string for BCMath precision.
 */
class CrmPipelineStage extends Model
{
    use HasTenant;

    protected $table = 'crm_pipeline_stages';

    protected $fillable = [
        'tenant_id',
        'name',
        'sort_order',
        'win_probability',
        'is_won',
        'is_lost',
        'is_active',
    ];

    protected $casts = [
        'sort_order'      => 'integer',
        'win_probability' => 'string',
        'is_won'          => 'boolean',
        'is_lost'         => 'boolean',
        'is_active'       => 'boolean',
    ];
}
