<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface SalesLedgerNoteServiceInterface
{
    public function list(array $payload): Result;

    public function create(array $payload): Result;

    public function update(int|string $id, array $payload): Result;

    public function delete(int|string $id, array $payload): Result;
}
