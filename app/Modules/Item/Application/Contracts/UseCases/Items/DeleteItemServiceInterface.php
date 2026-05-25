<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\Items;

use Modules\Core\Application\Results\Result;

interface DeleteItemServiceInterface
{
    public function execute(int|string $id): Result;
}
