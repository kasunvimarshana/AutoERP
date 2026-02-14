<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Repositories\ProductCategoryRepository;
use Illuminate\Support\Facades\DB;

class ProductCategoryService extends BaseService
{
    public function __construct(ProductCategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->repository->update($id, $data);
        });
    }
}
