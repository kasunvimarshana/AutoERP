<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ReturnAuthorizationModel extends BaseModel
{
    protected $table = 'return_authorizations';

    protected $fillable = [
        'tenant_id', 'rma_number', 'return_type', 'party_id', 'party_type',
        'reason', 'status', 'authorized_by', 'authorized_at', 'expires_at',
        'cancelled_at', 'stock_return_id', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'     => 'integer',
        'party_id'      => 'integer',
        'authorized_by' => 'integer',
        'authorized_at' => 'datetime',
        'expires_at'    => 'datetime',
        'cancelled_at'  => 'datetime',
        'stock_return_id' => 'integer',
        'metadata'      => 'array',
    ];
}
