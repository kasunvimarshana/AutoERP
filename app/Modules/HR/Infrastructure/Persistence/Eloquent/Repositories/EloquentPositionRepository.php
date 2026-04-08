<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PositionModel;

class EloquentPositionRepository extends EloquentRepository implements PositionRepositoryInterface
{
    public function __construct(PositionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PositionModel $model): Position => $this->mapModelToDomainEntity($model));
    }

    public function save(Position $position): Position
    {
        $savedModel = null;

        DB::transaction(function () use ($position, &$savedModel) {
            $data = [
                'tenant_id'     => $position->getTenantId(),
                'name'          => $position->getName()->value(),
                'code'          => $position->getCode()?->value(),
                'description'   => $position->getDescription(),
                'grade'         => $position->getGrade(),
                'department_id' => $position->getDepartmentId(),
                'metadata'      => $position->getMetadata()->toArray(),
                'is_active'     => $position->isActive(),
            ];

            if ($position->getId()) {
                $savedModel = $this->update($position->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PositionModel) {
            throw new \RuntimeException('Failed to save position.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByDepartment(int $departmentId): array
    {
        return $this->model->where('department_id', $departmentId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(PositionModel $model): Position
    {
        return new Position(
            tenantId:     $model->tenant_id,
            name:         new Name($model->name),
            code:         $model->code !== null ? new Code($model->code) : null,
            description:  $model->description,
            grade:        $model->grade,
            departmentId: $model->department_id,
            metadata:     isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            isActive:     (bool) $model->is_active,
            id:           $model->id,
            createdAt:    $model->created_at,
            updatedAt:    $model->updated_at,
        );
    }
}
