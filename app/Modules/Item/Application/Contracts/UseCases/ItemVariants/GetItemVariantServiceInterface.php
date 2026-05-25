<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemVariants;

use Modules\Core\Application\Results\Result;

interface GetItemVariantServiceInterface
{
    public function execute(int|string $id): Result;
}
