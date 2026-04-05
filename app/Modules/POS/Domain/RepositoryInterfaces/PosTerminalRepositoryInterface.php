<?php
declare(strict_types=1);
namespace Modules\POS\Domain\RepositoryInterfaces;

use Modules\POS\Domain\Entities\PosTerminal;

interface PosTerminalRepositoryInterface
{
    public function findById(int $id): ?PosTerminal;
    public function findByCode(int $tenantId, string $code): ?PosTerminal;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): PosTerminal;
    public function update(int $id, array $data): ?PosTerminal;
    public function delete(int $id): bool;
}
