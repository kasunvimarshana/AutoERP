<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * CrmLead entity.
 *
 * Represents a prospective customer before conversion.
 */
class CrmLead extends Model
{
    use HasTenant;

    protected $table = 'crm_leads';

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'assigned_to',
        'campaign_id',
        'notes',
    ];

    protected $casts = [
        'assigned_to' => 'integer',
        'campaign_id' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CrmCampaign::class, 'campaign_id');
    }
}
