<?php
declare(strict_types=1);
namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TaxGroupModel extends BaseModel
{
    protected $table = 'tax_groups';
    protected $fillable = [
        'tenant_id', 'name', 'code', 'description', 'is_active',
    ];
    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'is_active'  => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
