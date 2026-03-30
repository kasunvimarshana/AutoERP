<?php

declare(strict_types=1);

namespace Modules\Location\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Location\Application\Contracts\DeleteLocationServiceInterface;
use Modules\Location\Domain\Events\LocationDeleted;
use Modules\Location\Domain\Exceptions\LocationNotFoundException;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;

class DeleteLocationService extends BaseService implements DeleteLocationServiceInterface
{
    private LocationRepositoryInterface $locationRepository;

    public function __construct(LocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->locationRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $location = $this->locationRepository->find($id);
        if (! $location) {
            throw new LocationNotFoundException($id);
        }
        $tenantId = $location->getTenantId();
        $deleted  = $this->locationRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new LocationDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
