<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\Events\UomCategoryUpdated;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class UpdateUomCategoryService implements UpdateUomCategoryServiceInterface
{
    public function __construct(
        private readonly UomCategoryRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UomCategoryData $data): UomCategory
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new \DomainException("UomCategory not found: {$id}");
        }
        $updated = $this->repository->update($category, $data->toArray());
        event(new UomCategoryUpdated($data->tenantId, $id));
        return $updated;
    }
}
