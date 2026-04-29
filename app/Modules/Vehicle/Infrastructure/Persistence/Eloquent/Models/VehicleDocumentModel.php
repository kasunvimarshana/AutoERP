<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

class VehicleDocumentModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'vehicle_documents';

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'document_type',
        'document_number',
        'issued_at',
        'expires_at',
        'file_path',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'vehicle_id' => 'integer',
        'issued_at' => 'date',
        'expires_at' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }
}
