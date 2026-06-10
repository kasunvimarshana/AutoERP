<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Reporting\DTOs\ReportDefinition;

final class ReportDefinitionRegistry
{
    public function __construct(private readonly ReportCatalog $catalog) {}

    /**
     * @return array<string, ReportDefinition>
     */
    public function all(): array
    {
        return $this->catalog->all();
    }

    public function get(string $key): ReportDefinition
    {
        return $this->catalog->get($key);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function index(): array
    {
        return $this->catalog->index();
    }
}
