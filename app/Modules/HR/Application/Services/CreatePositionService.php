<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\Contracts\CreatePositionServiceInterface;
use Modules\HR\Application\DTOs\PositionData;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Events\PositionCreated;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class CreatePositionService extends BaseService implements CreatePositionServiceInterface
{
    public function __construct(private readonly PositionRepositoryInterface $positionRepository)
    {
        parent::__construct($positionRepository);
    }

    protected function handle(array $data): Position
    {
        $dto = PositionData::fromArray($data);

        $position = new Position(
            tenantId:     $dto->tenant_id,
            name:         new Name($dto->name),
            code:         $dto->code !== null ? new Code($dto->code) : null,
            description:  $dto->description,
            grade:        $dto->grade,
            departmentId: $dto->department_id,
            metadata:     $dto->metadata !== null ? new Metadata($dto->metadata) : null,
            isActive:     $dto->is_active,
        );

        $saved = $this->positionRepository->save($position);
        $this->addEvent(new PositionCreated($saved));

        return $saved;
    }
}
