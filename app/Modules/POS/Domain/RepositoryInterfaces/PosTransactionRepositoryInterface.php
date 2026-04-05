<?php
declare(strict_types=1);
namespace Modules\POS\Domain\RepositoryInterfaces;

use Modules\POS\Domain\Entities\PosTransaction;

interface PosTransactionRepositoryInterface
{
    public function findById(int $id): ?PosTransaction;
    public function findBySession(int $sessionId): array;
    public function create(array $data, array $lines): PosTransaction;
    public function updateStatus(int $id, string $status): ?PosTransaction;
}
