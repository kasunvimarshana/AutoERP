<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class WarehouseZoneModel extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_zones';

    protected $fillable = [
        'warehouse_id',
        'tenant_id',
        'name',
        'code',
        'type',
        'description',
        'capacity',
        'metadata',
        'is_active',
        'parent_zone_id',
        '_lft',
        '_rgt',
    ];

    protected $casts = [
        'metadata'       => 'array',
        'warehouse_id'   => 'integer',
        'tenant_id'      => 'integer',
        'parent_zone_id' => 'integer',
        '_lft'           => 'integer',
        '_rgt'           => 'integer',
        'capacity'       => 'float',
        'is_active'      => 'boolean',
    ];

    /**
     * Get all descendants of this zone node using nested set math.
     *
     * WHERE _lft > this._lft AND _rgt < this._rgt
     */
    public function getDescendants(): Collection
    {
        return static::where('warehouse_id', $this->warehouse_id)
            ->where('_lft', '>', $this->_lft)
            ->where('_rgt', '<', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    /**
     * Get all ancestors of this zone node using nested set math.
     *
     * WHERE _lft < this._lft AND _rgt > this._rgt, ordered by _lft
     */
    public function getAncestors(): Collection
    {
        return static::where('warehouse_id', $this->warehouse_id)
            ->where('_lft', '<', $this->_lft)
            ->where('_rgt', '>', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }
}
