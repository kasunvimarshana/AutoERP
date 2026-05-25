<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ItemAttributeValues;

use Modules\Core\Application\Results\Result;

interface UpdateItemAttributeValueServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
