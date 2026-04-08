<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class SupplierModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'tax_number',
        'registration_number',
        'currency_code',
        'credit_limit',
        'balance',
        'payment_terms_days',
        'status',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'credit_limit'       => 'decimal:4',
        'balance'            => 'decimal:4',
        'payment_terms_days' => 'integer',
        'metadata'           => 'array',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    /**
     * Polymorphic contacts for this supplier.
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(ContactModel::class, 'contactable');
    }
}
