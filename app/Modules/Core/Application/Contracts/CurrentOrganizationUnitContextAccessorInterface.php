<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Modules\Core\Application\DTO\CurrentOrganizationUnitContext;
use Modules\Core\Application\DTO\DataRecord;

interface CurrentOrganizationUnitContextAccessorInterface
{
    public function current(): ?CurrentOrganizationUnitContext;

    public function requireCurrent(): CurrentOrganizationUnitContext;

    public function currentOrganizationUnit(): ?DataRecord;

    public function currentOrganizationUnitId(): ?int;

    public function currentOrganizationUnitCode(): ?string;

    public function currentOrganizationUnitPath(): ?string;

    public function currentOrganizationUnitName(): ?string;

    public function currentTenantId(): ?int;

    public function currentApplicationId(): ?string;
}
