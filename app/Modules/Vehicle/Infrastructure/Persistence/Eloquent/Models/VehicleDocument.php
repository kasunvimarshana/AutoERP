<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleDocument extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_documents';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Vehicle\\Infrastructure\\Persistence\\Eloquent\\Models\\Vehicle',
            'vehicle_id'
        );
    }
}
