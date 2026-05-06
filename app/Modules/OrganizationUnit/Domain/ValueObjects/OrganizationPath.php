<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\ValueObjects;

/**
 * Immutable value object for organization unit path information.
 * Supports materialized path queries for efficient tree traversal.
 */
final class OrganizationPath
{
    private readonly string $path;
    private readonly int $depth;

    /**
     * Create a root path (depth = 0).
     */
    public static function root(int $organizationUnitId): self
    {
        return new self("/$organizationUnitId", 0);
    }

    /**
     * Create a child path.
     */
    public static function child(self $parent, int $organizationUnitId): self
    {
        $newPath = $parent->path . "/$organizationUnitId";
        $newDepth = $parent->depth + 1;

        return new self($newPath, $newDepth);
    }

    public function __construct(string $path, int $depth)
    {
        if ($depth < 0) {
            throw new \InvalidArgumentException('Path depth cannot be negative.');
        }

        $this->path = $path;
        $this->depth = $depth;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * Get IDs in the path hierarchy.
     *
     * @return array<int>
     */
    public function getHierarchy(): array
    {
        $parts = array_filter(explode('/', $this->path));

        return array_map('intval', $parts);
    }

    /**
     * Get the parent path.
     */
    public function getParent(): ?self
    {
        if ($this->depth === 0) {
            return null;
        }

        $parts = array_filter(explode('/', $this->path));
        array_pop($parts);
        $parentPath = '/' . implode('/', $parts);

        return new self($parentPath, $this->depth - 1);
    }

    /**
     * Check if this path is an ancestor of another.
     */
    public function isAncestorOf(self $other): bool
    {
        if ($this->depth >= $other->depth) {
            return false;
        }

        return str_starts_with($other->path, $this->path . '/');
    }

    /**
     * Check if this path is a descendant of another.
     */
    public function isDescendantOf(self $other): bool
    {
        return $other->isAncestorOf($this);
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
