<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class SupplierWhatsAppVerification extends TenantOwnedModel
{
    protected $table = 'supplier_whatsapp_verifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'supplier_contact_id' => 'integer',
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_by' => 'integer',
            'verified_at' => 'datetime',
            'superseded_at' => 'datetime',
            'created_by' => 'integer',
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(SupplierContact::class, 'supplier_contact_id');
    }
}
