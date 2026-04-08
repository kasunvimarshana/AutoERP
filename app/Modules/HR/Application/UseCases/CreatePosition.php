<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\DTOs\PositionData;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Events\PositionCreated;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class CreatePosition
{
    public function __construct(private readonly PositionRepositoryInterface $repo) {}

    public function execute(PositionData $data): Position
    {
        $position = new Position(
            tenantId:     $data->tenant_id,
            name:         new Name($data->name),
            code:         $data->code !== null ? new Code($data->code) : null,
            description:  $data->description,
            grade:        $data->grade,
            departmentId: $data->department_id,
            metadata:     $data->metadata !== null ? new Metadata($data->metadata) : null,
            isActive:     $data->is_active,
        );

        $saved = $this->repo->save($position);
        PositionCreated::dispatch($saved);

        return $saved;
    }
}
