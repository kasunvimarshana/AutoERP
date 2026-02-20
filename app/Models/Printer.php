<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Printer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'business_location_id', 'name',
        'connection_type', 'capability_profile', 'char_per_line',
        'ip_address', 'port', 'path', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public static function connectionTypes(): array
    {
        return [
            'network' => 'Network',
            'windows' => 'Windows',
            'linux' => 'Linux',
            'browser' => 'Browser',
        ];
    }

    public static function capabilityProfiles(): array
    {
        return [
            'default' => 'Default',
            'simple' => 'Simple',
            'SP2000' => 'Star Branded',
            'TEP-200M' => 'Epson TEP',
            'P822D' => 'P822D',
        ];
    }
}
