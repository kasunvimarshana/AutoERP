<?php
declare(strict_types=1);
namespace Modules\POS\Domain\RepositoryInterfaces;

use Modules\POS\Domain\Entities\PosSession;

interface PosSessionRepositoryInterface
{
    public function findById(int $id): ?PosSession;
    public function findOpenByTerminal(int $terminalId): ?PosSession;
    public function findAllByTenant(int $tenantId, int $perPage = 20, int $page = 1): array;
    public function create(array $data): PosSession;
    public function update(int $id, array $data): ?PosSession;
}
