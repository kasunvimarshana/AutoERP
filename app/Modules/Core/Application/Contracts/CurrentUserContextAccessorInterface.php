<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Modules\Core\Application\DTO\CurrentUserContext;

interface CurrentUserContextAccessorInterface
{
    public function current(): ?CurrentUserContext;

    public function requireCurrent(): CurrentUserContext;

    public function currentUserId(): ?int;

    public function currentTenantId(): ?int;

    public function currentOrganizationUnitId(): ?int;

    public function currentApplicationId(): ?string;
}
