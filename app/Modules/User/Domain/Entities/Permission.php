<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

class Permission
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    public function __construct(int $tenantId, string $name, ?int $id = null)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
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
}
