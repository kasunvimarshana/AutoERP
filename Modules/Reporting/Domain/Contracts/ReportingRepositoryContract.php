<?php

declare(strict_types=1);

namespace Modules\Reporting\Domain\Contracts;

use Modules\Core\Domain\Contracts\RepositoryContract;

interface ReportingRepositoryContract extends RepositoryContract
{
    public function findByType(string $type): \Illuminate\Database\Eloquent\Collection;

    public function findBySlug(string $slug): ?\Modules\Reporting\Domain\Entities\ReportDefinition;
}
