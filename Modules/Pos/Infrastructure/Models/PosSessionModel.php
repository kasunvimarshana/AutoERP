<?php

declare(strict_types=1);

namespace Modules\Pos\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class PosSessionModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'reference',
        'status',
        'opened_at',
        'closed_at',
        'currency',
        'opening_float',
        'closing_float',
        'total_sales',
        'total_refunds',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}
