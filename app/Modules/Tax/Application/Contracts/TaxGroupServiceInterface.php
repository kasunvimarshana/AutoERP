<?php
declare(strict_types=1);
namespace Modules\Tax\Application\Contracts;

use Modules\Tax\Domain\Entities\TaxGroup;

interface TaxGroupServiceInterface
{
    public function findById(int $id): TaxGroup;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): TaxGroup;
    public function update(int $id, array $data): TaxGroup;
    public function delete(int $id): void;
    public function activate(int $id): TaxGroup;
    public function deactivate(int $id): TaxGroup;
}
