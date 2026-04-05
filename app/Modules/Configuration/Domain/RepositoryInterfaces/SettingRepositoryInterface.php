<?php
declare(strict_types=1);
namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Configuration\Domain\Entities\Setting;

interface SettingRepositoryInterface
{
    public function findById(int $id): ?Setting;
    public function findByKey(int $tenantId, string $key): ?Setting;
    public function findByTenant(int $tenantId): array;
    public function set(int $tenantId, string $key, mixed $value, string $type = 'string', ?string $description = null): Setting;
    public function delete(int $tenantId, string $key): bool;
}
