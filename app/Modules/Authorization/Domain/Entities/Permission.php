<?php
declare(strict_types=1);
namespace Modules\Authorization\Domain\Entities;

class Permission
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $slug,
        private ?string $module,
        private ?string $description,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getModule(): ?string { return $this->module; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}
