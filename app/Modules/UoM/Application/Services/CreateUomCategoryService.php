<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\Events\UomCategoryCreated;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class CreateUomCategoryService implements CreateUomCategoryServiceInterface
{
    public function __construct(
        private readonly UomCategoryRepositoryInterface $repository,
    ) {}

    public function execute(UomCategoryData $data): UomCategory
    {
        $category = $this->repository->create($data->toArray());
        event(new UomCategoryCreated($data->tenantId, $category->id));
        return $category;
    }
}
