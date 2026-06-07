<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
use Modules\Hr\Enums\EmployeeRateType;
final readonly class EmployeeRateData { public function __construct(public EmployeeRateType $rateType, public string $amount, public ?int $currencyId = null, public ?string $effectiveFrom = null, public ?string $effectiveTo = null, public bool $isActive = true) {} }
