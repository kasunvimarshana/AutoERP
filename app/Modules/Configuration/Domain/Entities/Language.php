<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Entities;

class Language
{
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
