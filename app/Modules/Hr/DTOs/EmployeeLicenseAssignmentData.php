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

use Modules\Hr\Enums\EmployeeDocumentStatus;

final readonly class EmployeeLicenseAssignmentData
{
    public function __construct(public int $licenseId, public ?string $licenseNumber = null, public ?string $issuedDate = null, public ?string $expiryDate = null, public EmployeeDocumentStatus $status = EmployeeDocumentStatus::Pending) {}
}
