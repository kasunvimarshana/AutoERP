<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Exceptions\PurchaseReturnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;

class DeletePurchaseReturnService extends BaseService implements DeletePurchaseReturnServiceInterface
{
    public function __construct(private readonly PurchaseReturnRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseReturnNotFoundException($id);
        }

        return $this->repo->delete($id);
    }
}
