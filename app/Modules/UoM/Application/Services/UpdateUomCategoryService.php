<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\DTOs\UpdateUomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\Events\UomCategoryUpdated;
use Modules\UoM\Domain\Exceptions\UomCategoryNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class UpdateUomCategoryService extends BaseService implements UpdateUomCategoryServiceInterface
{
    private UomCategoryRepositoryInterface $categoryRepository;

    public function __construct(UomCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->categoryRepository = $repository;
    }

    protected function handle(array $data): UomCategory
    {
        $dto      = UpdateUomCategoryData::fromArray($data);
        $id       = (int) ($dto->id ?? 0);
        $category = $this->categoryRepository->find($id);

        if (! $category) {
            throw new UomCategoryNotFoundException($id);
        }

        $name = $dto->isProvided('name')
            ? (string) $dto->name
            : $category->getName();

        $code = $dto->isProvided('code')
            ? (string) $dto->code
            : $category->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $category->getDescription();

        $isActive = $dto->isProvided('isActive')
            ? (bool) $dto->isActive
            : $category->isActive();

        $category->updateDetails($name, $code, $description, $isActive);

        $saved = $this->categoryRepository->save($category);
        $this->addEvent(new UomCategoryUpdated($saved));

        return $saved;
    }
}
