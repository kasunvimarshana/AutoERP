<?php
namespace Modules\Setting\Domain\Contracts;
interface SettingRepositoryInterface
{
    public function get(string $key, string $tenantId, mixed $default = null): mixed;
    public function set(string $key, mixed $value, string $tenantId, string $group, string $type = 'string'): void;
    public function getGroup(string $group, string $tenantId): array;
    public function getAll(string $tenantId): array;
}
