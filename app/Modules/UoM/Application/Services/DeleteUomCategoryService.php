<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Domain\Events\UomCategoryDeleted;
use Modules\UoM\Domain\Exceptions\UomCategoryNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class DeleteUomCategoryService extends BaseService implements DeleteUomCategoryServiceInterface
{
    private UomCategoryRepositoryInterface $categoryRepository;

    public function __construct(UomCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->categoryRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $category = $this->categoryRepository->find($id);

        if (! $category) {
            throw new UomCategoryNotFoundException($id);
        }

        $tenantId = $category->getTenantId();
        $deleted  = $this->categoryRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new UomCategoryDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
