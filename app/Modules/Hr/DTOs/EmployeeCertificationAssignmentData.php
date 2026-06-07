<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
use Modules\Hr\Enums\EmployeeDocumentStatus;
final readonly class EmployeeCertificationAssignmentData { public function __construct(public int $certificationId, public ?string $certificateNumber = null, public ?string $issuedDate = null, public ?string $expiryDate = null, public EmployeeDocumentStatus $status = EmployeeDocumentStatus::Pending) {} }
