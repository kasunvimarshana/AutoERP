<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\DTOs\UpdatePositionData;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Events\PositionUpdated;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class UpdatePosition
{
    public function __construct(private readonly PositionRepositoryInterface $repo) {}

    public function execute(UpdatePositionData $data): Position
    {
        $id       = (int) ($data->id ?? 0);
        $position = $this->repo->find($id);
        if (! $position) {
            throw new PositionNotFoundException($id);
        }

        $name         = $data->isProvided('name') ? new Name((string) $data->name) : $position->getName();
        $code         = $data->isProvided('code') ? ($data->code !== null ? new Code($data->code) : null) : $position->getCode();
        $description  = $data->isProvided('description') ? $data->description : $position->getDescription();
        $grade        = $data->isProvided('grade') ? $data->grade : $position->getGrade();
        $departmentId = $data->isProvided('department_id') ? $data->department_id : $position->getDepartmentId();
        $metadata     = $data->isProvided('metadata') ? ($data->metadata !== null ? new Metadata($data->metadata) : null) : $position->getMetadata();
        $isActive     = $data->isProvided('is_active') ? (bool) $data->is_active : $position->isActive();

        $position->updateDetails($name, $code, $description, $grade, $departmentId, $metadata, $isActive);

        $saved = $this->repo->save($position);
        PositionUpdated::dispatch($saved);

        return $saved;
    }
}
