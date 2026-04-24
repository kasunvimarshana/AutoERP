<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\VariantAttribute;

interface VariantAttributeRepositoryInterface extends RepositoryInterface
{
    public function save(VariantAttribute $variantAttribute): VariantAttribute;

    public function find(int|string $id, array $columns = ['*']): ?VariantAttribute;
}
