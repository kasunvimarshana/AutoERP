<?php

declare(strict_types=1);

namespace Modules\Reporting\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use Modules\Reporting\Domain\Entities\ReportDefinition;

class ReportingRepository extends AbstractRepository implements ReportingRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = ReportDefinition::class;
    }

    public function findByType(string $type): Collection
    {
        return ReportDefinition::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->get();
    }

    public function findBySlug(string $slug): ?ReportDefinition
    {
        return ReportDefinition::query()
            ->where('slug', $slug)
            ->first();
    }
}
