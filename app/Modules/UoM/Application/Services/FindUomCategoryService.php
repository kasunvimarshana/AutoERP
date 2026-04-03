<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\FindUomCategoryServiceInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;

class FindUomCategoryService extends BaseService implements FindUomCategoryServiceInterface
{
    public function __construct(private readonly UomCategoryRepositoryInterface $categoryRepository)
    {
        parent::__construct($categoryRepository);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
