<?php

namespace Modules\OrganizationUnit\Domain\Entities;

use Modules\OrganizationUnit\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Domain\ValueObjects\Code;
use Modules\OrganizationUnit\Domain\ValueObjects\Metadata;
use Illuminate\Support\Collection;

class OrganizationUnit
{
    private ?int $id;
    private int $tenantId;
    private Name $name;
    private ?Code $code;
    private ?string $description;
    private Metadata $metadata;
    private ?int $parentId;
    private Collection $children; // Collection of OrganizationUnit entities
    private int $lft;
    private int $rgt;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        Name $name,
        ?Code $code = null,
        ?string $description = null,
        ?Metadata $metadata = null,
        ?int $parentId = null,
        ?int $id = null,
        int $lft = 0,
        int $rgt = 0,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->metadata = $metadata ?? new Metadata([]);
        $this->parentId = $parentId;
        $this->children = new Collection();
        $this->lft = $lft;
        $this->rgt = $rgt;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    // Getters...
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): Name { return $this->name; }
    public function getCode(): ?Code { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getChildren(): Collection { return $this->children; }
    public function getLft(): int { return $this->lft; }
    public function getRgt(): int { return $this->rgt; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    // Domain behaviour
    public function setParentId(?int $parentId): void
    {
        $this->parentId = $parentId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateDetails(Name $name, ?Code $code, ?string $description, ?Metadata $metadata): void
    {
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        if ($metadata) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addChild(OrganizationUnit $child): void
    {
        if ($this->children->contains('id', $child->getId())) {
            return;
        }
        $this->children->add($child);
        $child->setParentId($this->id);
    }

    public function setLftRgt(int $lft, int $rgt): void
    {
        $this->lft = $lft;
        $this->rgt = $rgt;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeChild(OrganizationUnit $child): void
    {
        $this->children = $this->children->reject(fn($c) => $c->getId() === $child->getId());
        $child->setParentId(null);
    }
}
