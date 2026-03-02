<?php

declare(strict_types=1);

namespace Modules\Reporting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Domain\Traits\HasTenant;

class ReportDefinition extends Model
{
    use HasTenant, SoftDeletes;

    protected $table = 'report_definitions';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'description',
        'filters',
        'columns',
        'sort_config',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'filters'     => 'array',
        'columns'     => 'array',
        'sort_config' => 'array',
        'is_system'   => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'report_definition_id');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class, 'report_definition_id');
    }
}
