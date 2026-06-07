<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Requests;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\EmployeeStatusChangeData;
use Modules\Hr\Enums\EmployeeStatus;
final class ChangeEmployeeStatusRequest extends TenantScopedRequest
{
    public function rules(): array { return ['tenant_id' => ['required', 'integer', 'min:1'], 'organization_unit_id' => ['nullable', 'integer', 'min:1'], 'status' => ['required', Rule::enum(EmployeeStatus::class)], 'reason' => ['nullable', 'string']]; }
    public function toData(): EmployeeStatusChangeData { return new EmployeeStatusChangeData(EmployeeStatus::from((string) $this->input('status')), $this->filled('reason') ? (string) $this->input('reason') : null, $this->currentUserId()); }
}
