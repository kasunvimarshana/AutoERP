<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\StoredConfigurationValue;

interface ConfigurationValueRepositoryInterface
{
    public function findExact(ConfigurationScopeContext $context, string $key): ?StoredConfigurationValue;
    /** @return LengthAwarePaginator<int, StoredConfigurationValue> */
    /** @param list<string> $keys */
    public function paginate(ConfigurationScopeContext $context, array $keys, int $page, int $perPage): LengthAwarePaginator;
    /** @return list<string> */
    public function keys(ConfigurationScopeContext $context): array;
    public function countTenantOverrides(string $key): int;
    public function countOrganizationUnitOverrides(string $key): int;
    /** @param array<string, mixed> $attributes */
    public function create(ConfigurationScopeContext $context, array $attributes): StoredConfigurationValue;
    /** @param array<string, mixed> $attributes */
    public function updateExpected(StoredConfigurationValue $current, int $expectedVersion, array $attributes): StoredConfigurationValue;
    public function deleteExpected(StoredConfigurationValue $current, int $expectedVersion): void;
}
