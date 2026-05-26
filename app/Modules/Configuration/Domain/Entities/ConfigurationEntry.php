<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Entities;

use Modules\Core\Domain\Entities\Entity;

final class ConfigurationEntry extends Entity
{
    public function __construct(
        int|string $id,
        private readonly string $key,
        private readonly mixed $value,
        private readonly string $source,
        private readonly ?string $description,
        private readonly ?string $updatedAt,
        private readonly string $scope,
        private readonly ?int $tenantId,
        private readonly ?int $organizationUnitId,
    ) {
        parent::__construct($id);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function organizationUnitId(): ?int
    {
        return $this->organizationUnitId;
    }
}
