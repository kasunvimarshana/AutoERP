<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
use Modules\Hr\Enums\EmployeeDocumentStatus;
use Modules\Hr\Enums\EmployeeDocumentType;
final readonly class EmployeeDocumentData { public function __construct(public EmployeeDocumentType $documentType, public ?string $documentNumber = null, public ?string $issuedDate = null, public ?string $expiryDate = null, public ?string $filePath = null, public EmployeeDocumentStatus $status = EmployeeDocumentStatus::Pending, public ?string $notes = null) {} }
