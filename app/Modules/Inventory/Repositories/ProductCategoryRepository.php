<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\ProductCategory;

class ProductCategoryRepository extends BaseRepository
{
    protected function model(): string
    {
        return ProductCategory::class;
    }
}
