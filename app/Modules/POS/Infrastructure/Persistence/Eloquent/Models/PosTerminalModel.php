<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PosTerminalModel extends BaseModel
{
    protected $table = 'pos_terminals';
    protected $fillable = ['tenant_id', 'warehouse_id', 'name', 'code', 'description', 'is_active'];
    protected $casts = [
        'id'           => 'int',
        'tenant_id'    => 'int',
        'warehouse_id' => 'int',
        'is_active'    => 'bool',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];
}
