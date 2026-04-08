<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class SupplierModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'contact_person',
        'payment_terms',
        'currency',
        'tax_number',
        'status',
        'type',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'address'        => 'array',
        'contact_person' => 'array',
        'attributes'     => 'array',
        'metadata'       => 'array',
    ];
}
