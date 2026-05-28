<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VoucherTypeServiceInterface
{
    public function list(array $criteria = []): Result;
    public function create(array $payload): Result;
    public function update(int $id, array $payload): Result;
    public function setActive(int $id, bool $isActive): Result;
}