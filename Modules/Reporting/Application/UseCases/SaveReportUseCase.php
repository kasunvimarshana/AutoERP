<?php

namespace Modules\Reporting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Reporting\Domain\Contracts\ReportRepositoryInterface;
use Modules\Reporting\Domain\Events\ReportSaved;

class SaveReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $report = $this->reportRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'user_id'     => $data['user_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'type'        => $data['type'] ?? 'custom',
                'data_source' => $data['data_source'] ?? null,
                'fields'      => $data['fields'] ?? [],
                'filters'     => $data['filters'] ?? [],
                'group_by'    => $data['group_by'] ?? [],
                'sort_by'     => $data['sort_by'] ?? [],
                'is_shared'   => $data['is_shared'] ?? false,
            ]);

            Event::dispatch(new ReportSaved(
                $report->id,
                $report->tenant_id,
                $report->name,
            ));

            return $report;
        });
    }
}
