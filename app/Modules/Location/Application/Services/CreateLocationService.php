<?php

declare(strict_types=1);

namespace Modules\Location\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Location\Application\Contracts\CreateLocationServiceInterface;
use Modules\Location\Application\DTOs\LocationData;
use Modules\Location\Domain\Entities\Location;
use Modules\Location\Domain\Events\LocationCreated;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;

class CreateLocationService extends BaseService implements CreateLocationServiceInterface
{
    private LocationRepositoryInterface $locationRepository;

    public function __construct(LocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->locationRepository = $repository;
    }

    protected function handle(array $data): Location
    {
        $dto = LocationData::fromArray($data);

        $name     = new Name($dto->name);
        $code     = $dto->code !== null ? new Code($dto->code) : null;
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;

        $location = new Location(
            tenantId:    $dto->tenant_id,
            name:        $name,
            type:        $dto->type,
            code:        $code,
            description: $dto->description,
            latitude:    $dto->latitude,
            longitude:   $dto->longitude,
            timezone:    $dto->timezone,
            metadata:    $metadata,
            parentId:    $dto->parent_id
        );

        $saved = $this->locationRepository->save($location);
        $this->addEvent(new LocationCreated($saved));

        return $saved;
    }
}
