<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class CustomerWhatsAppVerification extends TenantOwnedModel
{
    protected $table = 'customer_whatsapp_verifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'customer_contact_id' => 'integer',
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_by' => 'integer',
            'verified_at' => 'datetime',
            'superseded_at' => 'datetime',
            'created_by' => 'integer',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'customer_contact_id');
    }
}
