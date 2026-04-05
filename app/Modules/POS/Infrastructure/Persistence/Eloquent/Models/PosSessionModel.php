<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PosSessionModel extends BaseModel
{
    protected $table = 'pos_sessions';
    protected $fillable = [
        'tenant_id', 'terminal_id', 'cashier_id', 'status',
        'opening_balance', 'closing_balance', 'notes', 'opened_at', 'closed_at',
    ];
    protected $casts = [
        'id'              => 'int',
        'tenant_id'       => 'int',
        'terminal_id'     => 'int',
        'cashier_id'      => 'int',
        'opening_balance' => 'float',
        'closing_balance' => 'float',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
}
