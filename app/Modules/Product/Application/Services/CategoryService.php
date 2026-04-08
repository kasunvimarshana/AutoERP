<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CategoryServiceInterface;
use Modules\Product\Application\DTOs\CategoryData;
use Modules\Product\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class CategoryService extends BaseService implements CategoryServiceInterface
{
    public function __construct(CategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->repository->create($data);
    }

    public function create(CategoryData $dto): mixed
    {
        return $this->execute($dto->toArray());
    }

    public function getTree(int $tenantId): array
    {
        return $this->repository->getTree($tenantId);
    }

    public function move(int $id, ?int $parentId): mixed
    {
        return $this->update($id, ['parent_id' => $parentId]);
    }
}
