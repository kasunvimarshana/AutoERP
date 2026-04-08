<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\ReturnLine;

interface ReturnLineServiceInterface
{
    public function getReturnLine(string $tenantId, string $id): ReturnLine;

    public function getLinesForReturn(string $tenantId, string $returnType, string $returnId): array;

    public function addReturnLine(string $tenantId, array $data): ReturnLine;

    public function updateReturnLine(string $tenantId, string $id, array $data): ReturnLine;

    public function deleteReturnLine(string $tenantId, string $id): void;
}
