<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class UomConversion
{
    private ?int $id;

    private int $fromUomId;

    private int $toUomId;

    private string $factor;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $fromUomId,
        int $toUomId,
        string $factor,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->fromUomId = $fromUomId;
        $this->toUomId = $toUomId;
        $this->factor = $factor;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromUomId(): int
    {
        return $this->fromUomId;
    }

    public function getToUomId(): int
    {
        return $this->toUomId;
    }

    public function getFactor(): string
    {
        return $this->factor;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        int $fromUomId,
        int $toUomId,
        string $factor,
    ): void {
        $this->fromUomId = $fromUomId;
        $this->toUomId = $toUomId;
        $this->factor = $factor;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
