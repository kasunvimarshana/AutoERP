<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Reporting\Application\DTOs\GenerateReportDTO;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use Modules\Reporting\Domain\Entities\ReportDefinition;
use Modules\Reporting\Domain\Entities\ReportExport;
use Modules\Reporting\Domain\Entities\ReportSchedule;

class ReportingService implements ServiceContract
{
    public function __construct(private readonly ReportingRepositoryContract $repository) {}

    public function listDefinitions(): Collection
    {
        return $this->repository->all();
    }

    public function createDefinition(array $data): ReportDefinition
    {
        return DB::transaction(function () use ($data): ReportDefinition {
            /** @var ReportDefinition $def */
            $def = ReportDefinition::create([
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'type'        => $data['type'],
                'description' => $data['description'] ?? null,
                'filters'     => $data['filters'] ?? null,
                'columns'     => $data['columns'] ?? null,
                'sort_config' => $data['sort_config'] ?? null,
                'is_system'   => false,
                'is_active'   => true,
            ]);

            return $def;
        });
    }

    public function generateReport(GenerateReportDTO $dto): ReportExport
    {
        return DB::transaction(function () use ($dto): ReportExport {
            /** @var ReportExport $export */
            $export = ReportExport::create([
                'report_definition_id' => $dto->reportDefinitionId,
                'export_format'        => $dto->exportFormat,
                'status'               => 'pending',
                'filters_applied'      => $dto->filters,
            ]);

            return $export;
        });
    }

    public function scheduleReport(array $data): ReportSchedule
    {
        return DB::transaction(function () use ($data): ReportSchedule {
            /** @var ReportSchedule $schedule */
            $schedule = ReportSchedule::create([
                'report_definition_id' => $data['report_definition_id'],
                'frequency'            => $data['frequency'],
                'export_format'        => $data['export_format'],
                'recipients'           => $data['recipients'] ?? [],
                'next_run_at'          => $data['next_run_at'] ?? null,
                'is_active'            => true,
            ]);

            return $schedule;
        });
    }

    public function showDefinition(int $id): ReportDefinition
    {
        /** @var ReportDefinition $definition */
        $definition = $this->repository->findOrFail($id);

        return $definition;
    }

    /**
     * Update an existing report definition.
     *
     * @param array<string, mixed> $data
     */
    public function updateDefinition(int $id, array $data): ReportDefinition
    {
        return DB::transaction(function () use ($id, $data): ReportDefinition {
            /** @var ReportDefinition $definition */
            $definition = $this->repository->findOrFail($id);
            $definition->update($data);
            return $definition->fresh();
        });
    }

    /**
     * Delete a report definition (soft-delete via model).
     */
    public function deleteDefinition(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }

    /**
     * List all report schedules.
     */
    public function listSchedules(): \Illuminate\Database\Eloquent\Collection
    {
        return ReportSchedule::all();
    }

    /**
     * List all report exports.
     */
    public function listExports(): \Illuminate\Database\Eloquent\Collection
    {
        return ReportExport::all();
    }

    /**
     * Show a single report export.
     */
    public function showExport(int $id): ReportExport
    {
        /** @var ReportExport $export */
        $export = ReportExport::findOrFail($id);
        return $export;
    }

    /**
     * Show a single report schedule by ID.
     */
    public function showSchedule(int $id): ReportSchedule
    {
        /** @var ReportSchedule $schedule */
        $schedule = ReportSchedule::findOrFail($id);
        return $schedule;
    }

    /**
     * Update a report schedule.
     *
     * @param array<string, mixed> $data
     */
    public function updateSchedule(int $id, array $data): ReportSchedule
    {
        return DB::transaction(function () use ($id, $data): ReportSchedule {
            /** @var ReportSchedule $schedule */
            $schedule = ReportSchedule::findOrFail($id);
            $schedule->update($data);
            return $schedule->fresh();
        });
    }

    /**
     * Delete a report schedule.
     */
    public function deleteSchedule(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            /** @var ReportSchedule $schedule */
            $schedule = ReportSchedule::findOrFail($id);
            return (bool) $schedule->delete();
        });
    }
}
