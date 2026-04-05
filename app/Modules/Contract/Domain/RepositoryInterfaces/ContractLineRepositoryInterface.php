<?php
declare(strict_types=1);
namespace Modules\Contract\Domain\RepositoryInterfaces;
use Modules\Contract\Domain\Entities\ContractLine;
interface ContractLineRepositoryInterface {
    public function findById(int $id): ?ContractLine;
    public function findByContract(int $contractId): array;
    public function create(array $data): ContractLine;
    public function update(int $id, array $data): ?ContractLine;
    public function delete(int $id): bool;
}
