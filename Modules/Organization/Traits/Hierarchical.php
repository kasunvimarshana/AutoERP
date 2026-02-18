<?php

declare(strict_types=1);

namespace Modules\Organization\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hierarchical Trait
 *
 * Provides functionality for hierarchical (tree-like) data structures.
 * Supports parent-child relationships, ancestor/descendant queries,
 * and materialized path tracking for performance.
 */
trait Hierarchical
{
    /**
     * Boot the hierarchical trait for a model.
     */
    public static function bootHierarchical(): void
    {
        static::creating(function ($model) {
            $model->updateHierarchyInfo();
        });

        static::updating(function ($model) {
            if ($model->isDirty($model->getParentKeyName())) {
                $model->updateHierarchyInfo();
            }
        });

        static::saved(function ($model) {
            if ($model->wasChanged($model->getParentKeyName())) {
                $model->updateDescendantsHierarchyInfo();
            }
        });
    }

    /**
     * Get the parent key name.
     */
    public function getParentKeyName(): string
    {
        return 'parent_id';
    }

    /**
     * Get the parent relationship.
     */
    abstract public function parent(): BelongsTo;

    /**
     * Get the children relationship.
     */
    abstract public function children(): HasMany;

    /**
     * Get all ancestors (parents up to root).
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants (children down to leaves).
     */
    public function descendants(): Collection
    {
        $descendants = new Collection();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Get siblings (nodes with the same parent).
     */
    public function siblings(): Collection
    {
        if (!$this->{$this->getParentKeyName()}) {
            return static::whereNull($this->getParentKeyName())
                ->where('id', '!=', $this->id)
                ->get();
        }

        return $this->parent->children->where('id', '!=', $this->id);
    }

    /**
     * Get the root node of this tree.
     */
    public function root()
    {
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
        }

        return $current;
    }

    /**
     * Check if this node is a root (has no parent).
     */
    public function isRoot(): bool
    {
        return $this->{$this->getParentKeyName()} === null;
    }

    /**
     * Check if this node is a leaf (has no children).
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Check if this node is a child of the given node.
     */
    public function isChildOf($node): bool
    {
        return $this->{$this->getParentKeyName()} === $node->id;
    }

    /**
     * Check if this node is a descendant of the given node.
     */
    public function isDescendantOf($node): bool
    {
        if ($this->path && $node->path) {
            return str_starts_with($this->path, $node->path);
        }

        return $this->ancestors()->contains('id', $node->id);
    }

    /**
     * Check if this node is an ancestor of the given node.
     */
    public function isAncestorOf($node): bool
    {
        return $node->isDescendantOf($this);
    }

    /**
     * Get all root nodes.
     */
    public static function roots(): Builder
    {
        return static::whereNull((new static())->getParentKeyName());
    }

    /**
     * Get all leaf nodes.
     */
    public static function leaves(): Builder
    {
        return static::whereDoesntHave('children');
    }

    /**
     * Scope query to only root nodes.
     */
    public function scopeOnlyRoots(Builder $query): Builder
    {
        return $query->whereNull($this->getParentKeyName());
    }

    /**
     * Scope query to only leaf nodes.
     */
    public function scopeOnlyLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Scope query to descendants of a given node.
     */
    public function scopeDescendantsOf(Builder $query, $nodeId): Builder
    {
        $node = static::find($nodeId);
        
        if (!$node || !$node->path) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query->where('path', 'LIKE', $node->path.'%')
            ->where('id', '!=', $nodeId);
    }

    /**
     * Scope query to ancestors of a given node.
     */
    public function scopeAncestorsOf(Builder $query, $nodeId): Builder
    {
        $node = static::find($nodeId);
        
        if (!$node || !$node->path) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        $pathParts = array_filter(explode('/', trim($node->path, '/')));
        array_pop($pathParts); // Remove self

        if (empty($pathParts)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $pathParts);
    }

    /**
     * Update hierarchy information (level and path).
     */
    protected function updateHierarchyInfo(): void
    {
        if ($this->{$this->getParentKeyName()}) {
            $parent = $this->parent()->withoutGlobalScopes()->first();
            
            if ($parent) {
                $this->level = $parent->level + 1;
                $this->path = $parent->path.$this->id.'/';
            } else {
                $this->level = 0;
                $this->path = '/'.$this->id.'/';
            }
        } else {
            $this->level = 0;
            $this->path = '/'.$this->id.'/';
        }
    }

    /**
     * Update hierarchy info for all descendants.
     */
    protected function updateDescendantsHierarchyInfo(): void
    {
        foreach ($this->children as $child) {
            $child->updateHierarchyInfo();
            $child->saveQuietly();
            $child->updateDescendantsHierarchyInfo();
        }
    }

    /**
     * Get the depth/level of this node in the tree.
     */
    public function getDepth(): int
    {
        return $this->level ?? 0;
    }

    /**
     * Build a tree structure from a collection.
     */
    public static function buildTree(Collection $nodes, $parentId = null): Collection
    {
        $branch = new Collection();

        foreach ($nodes as $node) {
            if ($node->{$node->getParentKeyName()} === $parentId) {
                $children = static::buildTree($nodes, $node->id);
                
                if ($children->isNotEmpty()) {
                    $node->setRelation('children', $children);
                }
                
                $branch->push($node);
            }
        }

        return $branch;
    }
}
