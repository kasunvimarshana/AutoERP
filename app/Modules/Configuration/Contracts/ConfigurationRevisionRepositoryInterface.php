<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Configuration\Data\ConfigurationRevisionView;
use Modules\Configuration\Data\ConfigurationScopeContext;

interface ConfigurationRevisionRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function record(ConfigurationScopeContext $context, array $attributes): ConfigurationRevisionView;

    /** @return LengthAwarePaginator<int, ConfigurationRevisionView> */
    public function paginate(
        ConfigurationScopeContext $context,
        string $key,
        int $page,
        int $perPage,
    ): LengthAwarePaginator;
}
