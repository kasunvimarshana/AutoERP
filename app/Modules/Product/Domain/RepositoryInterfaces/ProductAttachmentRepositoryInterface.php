<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttachment;

interface ProductAttachmentRepositoryInterface extends RepositoryInterface
{
    public function save(ProductAttachment $productAttachment): ProductAttachment;

    public function find(int|string $id, array $columns = ['*']): ?ProductAttachment;
}
