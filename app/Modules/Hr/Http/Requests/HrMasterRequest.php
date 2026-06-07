<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Requests;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\HrCertificationData;
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\DTOs\HrDesignationData;
use Modules\Hr\DTOs\HrEmploymentTypeData;
use Modules\Hr\DTOs\HrLicenseData;
use Modules\Hr\DTOs\HrSkillData;
final class HrMasterRequest extends TenantScopedRequest
{
    public function rules(): array { $write = in_array($this->method(), ['POST', 'PUT', 'PATCH'], true); return ['tenant_id' => ['required', 'integer', 'min:1'], 'organization_unit_id' => ['nullable', 'integer', 'min:1'], 'parent_id' => ['nullable', 'integer', 'min:1'], 'code' => [$write ? 'required' : 'nullable', 'string', 'max:80'], 'name' => [$write ? 'required' : 'nullable', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'search' => ['nullable', 'string', 'max:255'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']]; }
    public function toData(string $type): object { $args = ['tenantId' => $this->tenantId(), 'code' => (string) $this->input('code'), 'name' => (string) $this->input('name'), 'organizationUnitId' => $this->organizationUnitId(), 'description' => $this->filled('description') ? (string) $this->input('description') : null, 'isActive' => (bool) $this->input('is_active', true)]; return match ($type) { HrDepartmentData::class => new HrDepartmentData(...$args, parentId: $this->filled('parent_id') ? (int) $this->input('parent_id') : null, sortOrder: (int) $this->input('sort_order', 0)), HrDesignationData::class => new HrDesignationData(...$args, sortOrder: (int) $this->input('sort_order', 0)), HrEmploymentTypeData::class => new HrEmploymentTypeData(...$args, sortOrder: (int) $this->input('sort_order', 0)), HrSkillData::class => new HrSkillData(...$args), HrCertificationData::class => new HrCertificationData(...$args), HrLicenseData::class => new HrLicenseData(...$args) }; }
}
