<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

final readonly class HrDepartmentData
{
    public function __construct(public int $tenantId, public string $code, public string $name, public ?int $organizationUnitId = null, public ?int $parentId = null, public ?string $description = null, public bool $isActive = true, public int $sortOrder = 0, public ?int $expectedVersion = null) {}
}
