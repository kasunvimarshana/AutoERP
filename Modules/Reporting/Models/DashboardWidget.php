<?php

declare(strict_types=1);

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Reporting\Enums\ChartType;
use Modules\Reporting\Enums\WidgetType;
use Modules\Tenant\Traits\TenantScoped;

/**
 * DashboardWidget Model
 *
 * Represents individual widgets on dashboards
 */
class DashboardWidget extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'dashboard_id',
        'report_id',
        'type',
        'chart_type',
        'title',
        'description',
        'configuration',
        'data_source',
        'refresh_interval',
        'order',
        'width',
        'height',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'type' => WidgetType::class,
        'chart_type' => ChartType::class,
        'configuration' => 'array',
        'data_source' => 'array',
        'refresh_interval' => 'integer',
        'order' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    /**
     * Get the dashboard this widget belongs to
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Get the report this widget is based on
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Update widget order
     */
    public function updateOrder(int $order): void
    {
        $this->update(['order' => $order]);
    }

    /**
     * Update widget position
     */
    public function updatePosition(int $x, int $y): void
    {
        $this->update([
            'position_x' => $x,
            'position_y' => $y,
        ]);
    }
}
