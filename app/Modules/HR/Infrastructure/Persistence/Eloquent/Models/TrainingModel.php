<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class TrainingModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_trainings';

    protected $fillable = [
        'tenant_id', 'title', 'description', 'trainer', 'location',
        'start_date', 'end_date', 'max_participants', 'status', 'metadata', 'is_active',
    ];

    protected $casts = [
        'tenant_id'        => 'integer',
        'max_participants' => 'integer',
        'is_active'        => 'boolean',
        'metadata'         => 'array',
    ];
}
