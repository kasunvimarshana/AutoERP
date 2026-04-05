<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Domain\Entities;

/**
 * Organisation Unit — node in a recursive, multi-tenant hierarchy.
 *
 * type: company | division | business_unit | department | team | branch | site | other
 *
 * path: materialized path of ancestor IDs, e.g. "/1/5/12/" — enables efficient
 *       sub-tree queries without recursive CTEs.
 * level: depth from root (root = 0).
 */
class OrgUnit
{
    public const TYPE_COMPANY       = 'company';
    public const TYPE_DIVISION      = 'division';
    public const TYPE_BUSINESS_UNIT = 'business_unit';
    public const TYPE_DEPARTMENT    = 'department';
    public const TYPE_TEAM          = 'team';
    public const TYPE_BRANCH        = 'branch';
    public const TYPE_SITE          = 'site';
    public const TYPE_OTHER         = 'other';

    public const VALID_TYPES = [
        self::TYPE_COMPANY, self::TYPE_DIVISION, self::TYPE_BUSINESS_UNIT,
        self::TYPE_DEPARTMENT, self::TYPE_TEAM, self::TYPE_BRANCH,
        self::TYPE_SITE, self::TYPE_OTHER,
    ];

    public function __construct(
        private ?int    $id,
        private int     $tenantId,
        private ?int    $parentId,
        private string  $type,
        private string  $code,
        private string  $name,
        private ?string $description,
        private ?int    $managerId,   // references a user/employee
        private int     $level,       // depth from root; root = 0
        private string  $path,        // materialized path "/1/5/12/"
        private bool    $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    // ── Accessors ────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getType(): string { return $this->type; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getManagerId(): ?int { return $this->managerId; }
    public function getLevel(): int { return $this->level; }
    public function getPath(): string { return $this->path; }
    public function isActive(): bool { return $this->isActive; }
    public function isRoot(): bool { return $this->parentId === null; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Mutators ─────────────────────────────────────────────────────────

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }

    public function update(
        string  $type,
        string  $code,
        string  $name,
        ?string $description,
        ?int    $managerId,
    ): void {
        $this->type        = $type;
        $this->code        = $code;
        $this->name        = $name;
        $this->description = $description;
        $this->managerId   = $managerId;
    }

    /**
     * Recompute level and path when the unit is moved to a new parent.
     * $parentPath is the path of the new parent (empty string if becoming root).
     * $parentLevel is the level of the new parent (-1 if becoming root).
     */
    public function moveTo(?int $newParentId, string $parentPath, int $parentLevel): void
    {
        $this->parentId = $newParentId;
        $this->level    = $parentLevel + 1;
        $this->path     = $parentPath . $this->id . '/';
    }

    /**
     * Build initial path after first save (id is now known).
     */
    public function initializePath(string $parentPath): void
    {
        $this->path = $parentPath . $this->id . '/';
    }
}
