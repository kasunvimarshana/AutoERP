<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

class Permission
{
    private ?int $id;

    private int $tenantId;

    private string $name;
    private string $guardName;
    private string $module;
    private ?string $description;

    public function __construct(int $tenantId, string $name,
        string $guardName,
        string $module,
        ?string $description = null, ?int $id = null)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->guardName = $guardName;
        $this->module = $module;
        $this->description = $description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGuardName(): string { return $this->guardName; }
    public function getModule(): string { return $this->module; }
    public function getDescription(): ?string { return $this->description; }
}
