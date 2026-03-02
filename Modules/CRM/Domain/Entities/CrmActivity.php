<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CrmActivity entity.
 *
 * Represents a sales activity (call, email, meeting, task, note) linked to
 * an opportunity or a lead.
 */
class CrmActivity extends Model
{
    use HasTenant;

    protected $table = 'crm_activities';

    protected $fillable = [
        'tenant_id',
        'opportunity_id',
        'lead_id',
        'activity_type',
        'title',
        'description',
        'scheduled_at',
        'completed_at',
        'assigned_to',
    ];

    protected $casts = [
        'opportunity_id' => 'integer',
        'lead_id'        => 'integer',
        'assigned_to'    => 'integer',
        'scheduled_at'   => 'datetime',
        'completed_at'   => 'datetime',
    ];
}
