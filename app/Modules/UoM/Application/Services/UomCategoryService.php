<?php
declare(strict_types=1);
namespace Modules\UoM\Application\Services;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UoM\Application\Contracts\UomCategoryServiceInterface;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
class UomCategoryService implements UomCategoryServiceInterface {
    public function __construct(private readonly UomCategoryRepositoryInterface $repo) {}
    public function findById(int $id): UomCategory {
        $e = $this->repo->findById($id);
        if (!$e) throw new NotFoundException("UomCategory", $id);
        return $e;
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }
    public function create(array $data): UomCategory { return $this->repo->create($data); }
    public function update(int $id, array $data): UomCategory {
        $e = $this->repo->update($id, $data);
        if (!$e) throw new NotFoundException("UomCategory", $id);
        return $e;
    }
    public function delete(int $id): bool {
        $e = $this->repo->findById($id);
        if (!$e) throw new NotFoundException("UomCategory", $id);
        return $this->repo->delete($id);
    }
}
