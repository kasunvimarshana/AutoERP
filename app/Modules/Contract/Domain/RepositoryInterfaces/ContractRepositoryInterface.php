<?php
declare(strict_types=1);
namespace Modules\Contract\Domain\RepositoryInterfaces;
use Modules\Contract\Domain\Entities\Contract;
interface ContractRepositoryInterface {
    public function findById(int $id): ?Contract;
    public function findByNumber(int $tenantId, string $number): ?Contract;
    public function findAllByTenant(int $tenantId, array $filters = []): array;
    public function findExpiring(int $tenantId, \DateTimeInterface $before): array;
    public function create(array $data): Contract;
    public function update(int $id, array $data): ?Contract;
    public function delete(int $id): bool;
}
