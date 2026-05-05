<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class VehicleDocumentModel extends Model
{
    use HasTenant, SoftDeletes;

    protected $table = 'fleet_vehicle_documents';

    protected $fillable = [
        'tenant_id', 'vehicle_id', 'document_type', 'document_number',
        'issuing_authority', 'issue_date', 'expiry_date', 'file_path', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
