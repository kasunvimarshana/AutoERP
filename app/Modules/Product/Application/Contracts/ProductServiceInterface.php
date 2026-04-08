<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface ProductServiceInterface extends ServiceInterface
{
    public function createProduct(array $data): mixed;
    public function updateProduct(string $id, array $data): mixed;
}
