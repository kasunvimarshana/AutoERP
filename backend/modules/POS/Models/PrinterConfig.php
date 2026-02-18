<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Printer Configuration Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $name
 * @property string $connection_type
 * @property string|null $ip_address
 * @property int|null $port
 * @property string $printer_type
 * @property bool $is_default
 * @property array|null $settings
 */
class PrinterConfig extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_printer_config';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'name',
        'connection_type',
        'ip_address',
        'port',
        'printer_type',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
