<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
final readonly class HrEmploymentTypeData { public function __construct(public int $tenantId, public string $code, public string $name, public ?int $organizationUnitId = null, public ?string $description = null, public bool $isActive = true, public int $sortOrder = 0) {} }
