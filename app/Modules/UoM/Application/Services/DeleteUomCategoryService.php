<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Domain\Events\UomCategoryDeleted;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class DeleteUomCategoryService implements DeleteUomCategoryServiceInterface
{
    public function __construct(
        private readonly UomCategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new \DomainException("UomCategory not found: {$id}");
        }
        $result = $this->repository->delete($category);
        event(new UomCategoryDeleted($category->tenantId, $id));
        return $result;
    }
}
