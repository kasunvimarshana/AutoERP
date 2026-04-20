<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\DeleteGrnServiceInterface;
use Modules\Purchase\Domain\Exceptions\GrnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;

class DeleteGrnService extends BaseService implements DeleteGrnServiceInterface
{
    public function __construct(private readonly GrnHeaderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new GrnNotFoundException($id);
        }

        return $this->repo->delete($id);
    }
}
