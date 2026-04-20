<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Domain\Exceptions\SupplierContactNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;

class DeleteSupplierContactService extends BaseService implements DeleteSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $supplierContactRepository)
    {
        parent::__construct($supplierContactRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $contact = $this->supplierContactRepository->find($id);

        if (! $contact) {
            throw new SupplierContactNotFoundException($id);
        }

        return $this->supplierContactRepository->delete($id);
    }
}
