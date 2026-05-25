<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ComboItems;

use Modules\Core\Application\Results\Result;

interface GetComboItemServiceInterface
{
    public function execute(int|string $id): Result;
}
