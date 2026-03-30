<?php

declare(strict_types=1);

namespace Modules\Location\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Location\Application\Contracts\UpdateLocationServiceInterface;
use Modules\Location\Application\DTOs\UpdateLocationData;
use Modules\Location\Domain\Entities\Location;
use Modules\Location\Domain\Events\LocationUpdated;
use Modules\Location\Domain\Exceptions\LocationNotFoundException;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;

class UpdateLocationService extends BaseService implements UpdateLocationServiceInterface
{
    private LocationRepositoryInterface $locationRepository;

    public function __construct(LocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->locationRepository = $repository;
    }

    protected function handle(array $data): Location
    {
        $dto      = UpdateLocationData::fromArray($data);
        $id       = (int) ($dto->id ?? 0);
        $location = $this->locationRepository->find($id);
        if (! $location) {
            throw new LocationNotFoundException($id);
        }

        // isProvided() distinguishes "field was absent" from "field was sent as null",
        // enabling safe partial updates that never unintentionally clear existing data.
        $name = $dto->isProvided('name')
            ? new Name((string) $dto->name)
            : $location->getName();

        $type = $dto->isProvided('type')
            ? (string) $dto->type
            : $location->getType();

        $code = $dto->isProvided('code')
            ? ($dto->code !== null ? new Code($dto->code) : null)
            : $location->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $location->getDescription();

        $latitude = $dto->isProvided('latitude')
            ? $dto->latitude
            : $location->getLatitude();

        $longitude = $dto->isProvided('longitude')
            ? $dto->longitude
            : $location->getLongitude();

        $timezone = $dto->isProvided('timezone')
            ? $dto->timezone
            : $location->getTimezone();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $location->getMetadata();

        $location->updateDetails($name, $type, $code, $description, $latitude, $longitude, $timezone, $metadata);

        // Only move the node when parent_id was explicitly supplied and differs.
        if ($dto->isProvided('parent_id') && $dto->parent_id !== $location->getParentId()) {
            $this->locationRepository->moveNode($id, $dto->parent_id);
        }

        $saved = $this->locationRepository->save($location);
        $this->addEvent(new LocationUpdated($saved));

        return $saved;
    }
}
