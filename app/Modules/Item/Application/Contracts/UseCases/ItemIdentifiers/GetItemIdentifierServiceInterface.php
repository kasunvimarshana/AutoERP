<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemIdentifiers;

use Modules\Core\Application\Results\Result;

interface GetItemIdentifierServiceInterface
{
    public function execute(int|string $id): Result;
}
