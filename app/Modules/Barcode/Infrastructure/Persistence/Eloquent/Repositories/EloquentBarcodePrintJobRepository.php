<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Barcode\Domain\Entities\BarcodePrintJob;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\BarcodePrintJobModel;

class EloquentBarcodePrintJobRepository implements BarcodePrintJobRepositoryInterface
{
    public function __construct(private readonly BarcodePrintJobModel $model) {}

    private function hydrate(BarcodePrintJobModel $m): BarcodePrintJob
    {
        return new BarcodePrintJob(
            $m->id,
            $m->tenant_id,
            $m->barcode_definition_id,
            $m->label_template_id,
            $m->status,
            $m->printer_target,
            (int) $m->copies,
            $m->rendered_output,
            $m->variables ?? [],
            $m->error_message,
            $m->queued_at,
            $m->completed_at,
        );
    }

    private function persist(BarcodePrintJob $job): BarcodePrintJobModel
    {
        $data = [
            'tenant_id'             => $job->getTenantId(),
            'barcode_definition_id' => $job->getBarcodeDefinitionId(),
            'label_template_id'     => $job->getLabelTemplateId(),
            'status'                => $job->getStatus(),
            'printer_target'        => $job->getPrinterTarget(),
            'copies'                => $job->getCopies(),
            'rendered_output'       => $job->getRenderedOutput(),
            'variables'             => $job->getVariables(),
            'error_message'         => $job->getErrorMessage(),
            'queued_at'             => $job->getQueuedAt(),
            'completed_at'          => $job->getCompletedAt(),
        ];

        if ($job->getId() === null) {
            return $this->model->newQuery()->create($data);
        }

        $m = $this->model->newQuery()->findOrFail($job->getId());
        $m->update($data);

        return $m->fresh();
    }

    public function findById(int $id): ?BarcodePrintJob
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->hydrate($m) : null;
    }

    /** @return BarcodePrintJob[] */
    public function findAll(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('queued_at')
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    /** @return BarcodePrintJob[] */
    public function findByStatus(int $tenantId, string $status): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderByDesc('queued_at')
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    /** @return BarcodePrintJob[] */
    public function findByDefinition(int $tenantId, int $barcodeDefinitionId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('barcode_definition_id', $barcodeDefinitionId)
            ->orderByDesc('queued_at')
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    public function save(BarcodePrintJob $job): BarcodePrintJob
    {
        return $this->hydrate($this->persist($job));
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}
