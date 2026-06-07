<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Hr\Models\HrDepartment;
final class HrDepartmentService extends HrMasterService
{
    protected string $modelClass = HrDepartment::class; protected string $label = 'HR department'; protected bool $hasSortOrder = true;
    protected function validateData(object $data): void { parent::validateData($data); if ($data->parentId !== null) { $parent = HrDepartment::query()->findOrFail($data->parentId); if ((int) $parent->tenant_id !== $data->tenantId) { throw new InvalidArgumentException('Parent department belongs to a different tenant.'); } } }
    protected function attributes(object $data): array { return [...parent::attributes($data), 'parent_id' => $data->parentId]; }
    public function update(Model $model, object $data): Model { if ($data->parentId !== null && $data->parentId === (int) $model->getKey()) { throw new InvalidArgumentException('Department cannot be its own parent.'); } return parent::update($model, $data); }
}
