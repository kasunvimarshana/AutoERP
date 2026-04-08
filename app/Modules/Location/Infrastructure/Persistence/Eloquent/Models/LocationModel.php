<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class LocationModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'code',
        'description',
        'latitude',
        'longitude',
        'timezone',
        'metadata',
        'parent_id',
        '_lft',
        '_rgt',
    ];

    protected $casts = [
        'metadata'  => 'array',
        'tenant_id' => 'integer',
        'parent_id' => 'integer',
        '_lft'      => 'integer',
        '_rgt'      => 'integer',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get all descendants of this node using nested set math.
     *
     * WHERE _lft > this._lft AND _rgt < this._rgt
     */
    public function getDescendants(): Collection
    {
        return static::where('tenant_id', $this->tenant_id)
            ->where('_lft', '>', $this->_lft)
            ->where('_rgt', '<', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    /**
     * Get all ancestors of this node using nested set math.
     *
     * WHERE _lft < this._lft AND _rgt > this._rgt, ordered by _lft
     */
    public function getAncestors(): Collection
    {
        return static::where('tenant_id', $this->tenant_id)
            ->where('_lft', '<', $this->_lft)
            ->where('_rgt', '>', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }
}
