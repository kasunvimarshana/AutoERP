<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemIdentifiers;

use Modules\Core\Application\Results\Result;

interface CreateItemIdentifierServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
