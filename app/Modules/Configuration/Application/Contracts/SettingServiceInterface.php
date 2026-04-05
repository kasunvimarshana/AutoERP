<?php
declare(strict_types=1);
namespace Modules\Configuration\Application\Contracts;

use Modules\Configuration\Domain\Entities\Setting;

interface SettingServiceInterface
{
    public function get(int $tenantId, string $key): mixed;
    public function set(int $tenantId, string $key, mixed $value, string $type = 'string', ?string $description = null): Setting;
    public function getAll(int $tenantId): array;
    public function delete(int $tenantId, string $key): bool;
}
