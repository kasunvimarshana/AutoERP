<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
use Modules\Hr\Enums\EmployeeStatus;
final readonly class EmployeeStatusChangeData { public function __construct(public EmployeeStatus $newStatus, public ?string $reason = null, public ?int $changedBy = null) {} }
