<?php

namespace App\Modules\Reporting\Models;

use App\Modules\Reporting\Enums\WidgetType;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DashboardWidget Model
 *
 * Represents a widget on a dashboard.
 */
class DashboardWidget extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Reporting\DashboardWidgetFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'dashboard_id',
        'widget_type',
        'title',
        'config',
        'position_x',
        'position_y',
        'width',
        'height',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'widget_type' => WidgetType::class,
        'config' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Get the dashboard
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
