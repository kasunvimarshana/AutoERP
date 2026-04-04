<?php
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class AccountModel extends BaseModel
{
    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'parent_id',
        'currency',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
