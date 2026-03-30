<?php

declare(strict_types=1);

namespace Modules\Location\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Location\Application\Contracts\MoveLocationServiceInterface;
use Modules\Location\Application\DTOs\MoveLocationData;
use Modules\Location\Domain\Events\LocationMoved;
use Modules\Location\Domain\Exceptions\LocationNotFoundException;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;

class MoveLocationService extends BaseService implements MoveLocationServiceInterface
{
    private LocationRepositoryInterface $locationRepository;

    public function __construct(LocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->locationRepository = $repository;
    }

    protected function handle(array $data): mixed
    {
        $id  = $data['id'];
        $dto = MoveLocationData::fromArray($data);

        $location = $this->locationRepository->find($id);
        if (! $location) {
            throw new LocationNotFoundException($id);
        }

        $oldParentId = $location->getParentId();
        if ($oldParentId === $dto->parent_id) {
            return null;
        }

        $this->locationRepository->moveNode($id, $dto->parent_id);
        $updated = $this->locationRepository->find($id);
        $this->addEvent(new LocationMoved($updated, $oldParentId));

        return null;
    }
}
