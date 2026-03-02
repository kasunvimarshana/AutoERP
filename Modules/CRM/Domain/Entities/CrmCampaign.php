<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CrmCampaign entity.
 *
 * Represents a marketing campaign that can be attributed to leads.
 * budget is cast to string for BCMath precision.
 */
class CrmCampaign extends Model
{
    use HasTenant;

    protected $table = 'crm_campaigns';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'status',
        'start_date',
        'end_date',
        'budget',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'string',
    ];
}
