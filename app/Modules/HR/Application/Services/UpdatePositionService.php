<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\Contracts\UpdatePositionServiceInterface;
use Modules\HR\Application\DTOs\UpdatePositionData;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Events\PositionUpdated;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class UpdatePositionService extends BaseService implements UpdatePositionServiceInterface
{
    public function __construct(private readonly PositionRepositoryInterface $positionRepository)
    {
        parent::__construct($positionRepository);
    }

    protected function handle(array $data): Position
    {
        $dto      = UpdatePositionData::fromArray($data);
        $id       = (int) ($dto->id ?? 0);
        $position = $this->positionRepository->find($id);
        if (! $position) {
            throw new PositionNotFoundException($id);
        }

        $name = $dto->isProvided('name')
            ? new Name((string) $dto->name)
            : $position->getName();

        $code = $dto->isProvided('code')
            ? ($dto->code !== null ? new Code($dto->code) : null)
            : $position->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $position->getDescription();

        $grade = $dto->isProvided('grade')
            ? $dto->grade
            : $position->getGrade();

        $departmentId = $dto->isProvided('department_id')
            ? $dto->department_id
            : $position->getDepartmentId();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $position->getMetadata();

        $isActive = $dto->isProvided('is_active')
            ? (bool) $dto->is_active
            : $position->isActive();

        $position->updateDetails($name, $code, $description, $grade, $departmentId, $metadata, $isActive);

        $saved = $this->positionRepository->save($position);
        $this->addEvent(new PositionUpdated($saved));

        return $saved;
    }
}
