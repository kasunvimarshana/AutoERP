<?php

declare(strict_types=1);

namespace Modules\Crm\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class ActivityModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'crm_activities';

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'lead_id',
        'type',
        'subject',
        'description',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ContactModel::class, 'contact_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadModel::class, 'lead_id');
    }
}
