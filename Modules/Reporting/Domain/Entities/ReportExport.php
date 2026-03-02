<?php

declare(strict_types=1);

namespace Modules\Reporting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

class ReportExport extends Model
{
    use HasTenant;

    protected $table = 'report_exports';

    protected $fillable = [
        'tenant_id',
        'report_definition_id',
        'export_format',
        'status',
        'file_path',
        'error_message',
        'filters_applied',
        'completed_at',
    ];

    protected $casts = [
        'filters_applied' => 'array',
        'completed_at'    => 'datetime',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
