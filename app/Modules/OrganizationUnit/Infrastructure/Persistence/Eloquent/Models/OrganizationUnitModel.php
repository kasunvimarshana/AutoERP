<?php

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationUnitModel extends Model
{
    use SoftDeletes;

    protected $table = 'organization_units';
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'metadata',
        'parent_id',
        '_lft',
        '_rgt',
    ];
    protected $casts = [
        'metadata' => 'array',
        'tenant_id' => 'integer',
        'parent_id' => 'integer',
        '_lft' => 'integer',
        '_rgt' => 'integer',
    ];

    public function attachments()
    {
        return $this->hasMany(OrganizationUnitAttachmentModel::class, 'organization_unit_id');
    }

    // Nested set helpers (will be used by the repository)

    /**
     * Get all descendants of this node.
     */
    public function getDescendants(): \Illuminate\Support\Collection
    {
        return static::where('tenant_id', $this->tenant_id)
            ->where('_lft', '>', $this->_lft)
            ->where('_rgt', '<', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    /**
     * Get all ancestors of this node.
     */
    public function getAncestors(): \Illuminate\Support\Collection
    {
        return static::where('tenant_id', $this->tenant_id)
            ->where('_lft', '<', $this->_lft)
            ->where('_rgt', '>', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }
}
