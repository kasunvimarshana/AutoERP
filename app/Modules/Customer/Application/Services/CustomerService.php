<?php
declare(strict_types=1);
namespace Modules\Customer\Application\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
class CustomerService implements CustomerServiceInterface {
    public function __construct(private readonly CustomerRepositoryInterface $repo) {}
    public function findById(int $id): Customer {
        $e = $this->repo->findById($id);
        if (!$e) throw new CustomerNotFoundException($id);
        return $e;
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }
    public function create(array $data): Customer { return $this->repo->create($data); }
    public function update(int $id, array $data): Customer {
        $e = $this->repo->update($id, $data);
        if (!$e) throw new CustomerNotFoundException($id);
        return $e;
    }
    public function delete(int $id): bool {
        $e = $this->repo->findById($id);
        if (!$e) throw new CustomerNotFoundException($id);
        return $this->repo->delete($id);
    }
}
