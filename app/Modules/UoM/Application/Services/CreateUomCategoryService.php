<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\Events\UomCategoryCreated;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class CreateUomCategoryService extends BaseService implements CreateUomCategoryServiceInterface
{
    private UomCategoryRepositoryInterface $categoryRepository;

    public function __construct(UomCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->categoryRepository = $repository;
    }

    protected function handle(array $data): UomCategory
    {
        $dto = UomCategoryData::fromArray($data);

        $category = new UomCategory(
            tenantId:    $dto->tenantId,
            name:        $dto->name,
            code:        $dto->code,
            description: $dto->description,
            isActive:    $dto->isActive,
        );

        $saved = $this->categoryRepository->save($category);
        $this->addEvent(new UomCategoryCreated($saved));

        return $saved;
    }
}
