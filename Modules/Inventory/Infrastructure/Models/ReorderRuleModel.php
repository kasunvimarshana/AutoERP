<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReorderRuleModel extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_reorder_rules';

    protected $guarded = [];

    protected $casts = [
        'reorder_point' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
