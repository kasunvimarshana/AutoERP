<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountModel extends Model
{
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'subtype',
        'description',
        'currency',
        'balance',
        'is_system',
        'parent_id',
        'status',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'balance'    => 'float',
        'is_system'  => 'boolean',
        'attributes' => 'array',
        'metadata'   => 'array',
    ];
}
