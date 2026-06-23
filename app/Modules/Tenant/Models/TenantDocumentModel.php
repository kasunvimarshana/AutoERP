<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\TenantOwnedModel;

final class TenantDocumentModel extends TenantOwnedModel
{
    use HasImmutableTenantOwnership;

    protected $table = 'tenant_documents';

    protected $fillable = [
        'tenant_id', 'name', 'document_type', 'storage_disk', 'storage_path',
        'original_filename', 'mime_type', 'size_bytes', 'checksum_sha256',
        'metadata', 'row_version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'size_bytes' => 'integer',
            'metadata' => 'array',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
