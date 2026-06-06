<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\DataRecord;

interface CurrentTenantContextAccessorInterface
{
    public function current(): ?CurrentTenantContext;

    public function requireCurrent(): CurrentTenantContext;

    public function currentTenant(): ?DataRecord;

    public function currentTenantId(): ?int;

    public function currentTenantCode(): ?string;

    public function currentTenantUuid(): ?string;

    public function currentTenantDomain(): ?string;

    public function currentApplicationId(): ?string;
}
