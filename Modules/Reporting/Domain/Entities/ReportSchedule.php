<?php

declare(strict_types=1);

namespace Modules\Reporting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

class ReportSchedule extends Model
{
    use HasTenant;

    protected $table = 'report_schedules';

    protected $fillable = [
        'tenant_id',
        'report_definition_id',
        'frequency',
        'export_format',
        'recipients',
        'last_run_at',
        'next_run_at',
        'is_active',
    ];

    protected $casts = [
        'recipients'  => 'array',
        'is_active'   => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
