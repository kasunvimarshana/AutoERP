<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Hr\Models\HrEmployee;
final class EmployeeRelationQueryService
{
    public function paginate(HrEmployee $employee, string $relation, int $perPage): LengthAwarePaginator { $with = match ($relation) { 'skillAssignments' => ['skill'], 'certificationAssignments' => ['certification'], 'licenseAssignments' => ['license'], 'rates' => ['currency'], default => [] }; return $employee->{$relation}()->with($with)->latest('id')->paginate($perPage); }
    public function find(HrEmployee $employee, string $relation, int $id): Model { return $employee->{$relation}()->findOrFail($id); }
}
