<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use InvalidArgumentException;
use Modules\Hr\DTOs\EmployeeDocumentData;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeDocument;
final class EmployeeDocumentService
{
    public function __construct(private readonly EmployeeValidationService $validator) {}
    public function create(HrEmployee $employee, EmployeeDocumentData $data): HrEmployeeDocument { $this->validator->assertDateRange($data->issuedDate, $data->expiryDate); return $employee->documents()->create($this->attributes($employee, $data)); }
    public function update(HrEmployee $employee, HrEmployeeDocument $document, EmployeeDocumentData $data): HrEmployeeDocument { $this->owned($employee, $document); $this->validator->assertDateRange($data->issuedDate, $data->expiryDate); $document->fill($this->attributes($employee, $data, false))->save(); return $document->refresh(); }
    public function delete(HrEmployee $employee, HrEmployeeDocument $document): void { $this->owned($employee, $document); $document->delete(); }
    public function replace(HrEmployee $employee, array $rows): void { $employee->documents()->delete(); foreach ($rows as $row) { $this->create($employee, $row); } }
    private function attributes(HrEmployee $employee, EmployeeDocumentData $data, bool $scope = true): array { return [...($scope ? ['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id] : []), 'document_type' => $data->documentType, 'document_number' => $data->documentNumber, 'issued_date' => $data->issuedDate, 'expiry_date' => $data->expiryDate, 'file_path' => $data->filePath, 'status' => $data->status, 'notes' => $data->notes]; }
    private function owned(HrEmployee $employee, HrEmployeeDocument $row): void { if ((int) $row->employee_id !== (int) $employee->getKey()) { throw new InvalidArgumentException('Employee document does not belong to the employee.'); } }
}
