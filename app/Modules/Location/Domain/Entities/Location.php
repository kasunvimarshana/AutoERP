<?php

declare(strict_types=1);

namespace Modules\Location\Domain\Entities;

use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;

class Location
{
    private ?int $id;

    private int $tenantId;

    private Name $name;

    private ?Code $code;

    private string $type;

    private ?string $description;

    private ?float $latitude;

    private ?float $longitude;

    private ?string $timezone;

    private Metadata $metadata;

    private ?int $parentId;

    private Collection $children;

    private int $lft;

    private int $rgt;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        Name $name,
        string $type,
        ?Code $code = null,
        ?string $description = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $timezone = null,
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
        $this->type = $type;
        $this->code = $code;
        $this->description = $description;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timezone = $timezone;
        $this->metadata = $metadata ?? new Metadata([]);
        $this->parentId = $parentId;
        $this->children = new Collection;
        $this->lft = $lft;
        $this->rgt = $rgt;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getCode(): ?Code
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setParentId(?int $parentId): void
    {
        $this->parentId = $parentId;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        Name $name,
        string $type,
        ?Code $code,
        ?string $description,
        ?float $latitude,
        ?float $longitude,
        ?string $timezone,
        ?Metadata $metadata
    ): void {
        $this->name = $name;
        $this->type = $type;
        $this->code = $code;
        $this->description = $description;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timezone = $timezone;
        $this->metadata = $metadata ?? new Metadata([]);
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function addChild(Location $child): void
    {
        if ($this->children->contains(fn ($c) => $c->getId() === $child->getId())) {
            return;
        }
        $this->children->add($child);
        $child->setParentId($this->id);
    }

    public function removeChild(Location $child): void
    {
        $this->children = $this->children->reject(fn ($c) => $c->getId() === $child->getId());
        $child->setParentId(null);
    }

    public function setLftRgt(int $lft, int $rgt): void
    {
        $this->lft = $lft;
        $this->rgt = $rgt;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
