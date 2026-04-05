<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Services;

use Modules\Barcode\Application\Contracts\PrintBarcodeLabelServiceInterface;
use Modules\Barcode\Domain\Entities\BarcodePrintJob;
use Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface;
use Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher;

class PrintBarcodeLabelService implements PrintBarcodeLabelServiceInterface
{
    public function __construct(
        private readonly BarcodePrintJobRepositoryInterface  $jobs,
        private readonly BarcodeDefinitionRepositoryInterface $definitions,
        private readonly LabelTemplateRepositoryInterface    $templates,
        private readonly BarcodePrinterDispatcher            $printerDispatcher,
    ) {}

    public function queue(
        int     $tenantId,
        int     $barcodeDefinitionId,
        ?int    $labelTemplateId,
        string  $format,
        ?string $printerTarget,
        int     $copies,
        array   $variables,
    ): BarcodePrintJob {
        // Ensure the definition exists
        $definition = $this->definitions->findById($barcodeDefinitionId);
        if ($definition === null) {
            throw BarcodeNotFoundException::withId($barcodeDefinitionId);
        }

        $job = new BarcodePrintJob(
            null,
            $tenantId,
            $barcodeDefinitionId,
            $labelTemplateId,
            BarcodePrintJob::STATUS_PENDING,
            $printerTarget,
            max(1, $copies),
            null,
            $variables,
            null,
            new \DateTime(),
            null,
        );

        return $this->jobs->save($job);
    }

    public function process(int $jobId): BarcodePrintJob
    {
        $job = $this->getById($jobId);

        if (!$job->isPending()) {
            throw new \LogicException(
                sprintf('Print job %d has status "%s" and cannot be processed.', $jobId, $job->getStatus())
            );
        }

        $job->markProcessing();
        $this->jobs->save($job);

        try {
            $definition = $this->definitions->findById($job->getBarcodeDefinitionId());
            if ($definition === null) {
                throw BarcodeNotFoundException::withId($job->getBarcodeDefinitionId());
            }

            $template = $job->getLabelTemplateId() !== null
                ? $this->templates->findById($job->getLabelTemplateId())
                : null;

            // Use the template's format if available, otherwise fall back to a format
            // derived from the printer target (zpl default)
            $format = $template?->getFormat() ?? 'zpl';

            $rendered = $this->printerDispatcher->render(
                $format,
                $definition,
                $template,
                $job->getVariables(),
            );

            $job->markCompleted($rendered);
        } catch (\Throwable $e) {
            $job->markFailed($e->getMessage());
        }

        return $this->jobs->save($job);
    }

    public function getById(int $id): BarcodePrintJob
    {
        $job = $this->jobs->findById($id);

        if ($job === null) {
            throw BarcodeNotFoundException::withId($id);
        }

        return $job;
    }

    /** @return BarcodePrintJob[] */
    public function listAll(int $tenantId): array
    {
        return $this->jobs->findAll($tenantId);
    }

    /** @return BarcodePrintJob[] */
    public function listByStatus(int $tenantId, string $status): array
    {
        return $this->jobs->findByStatus($tenantId, $status);
    }

    public function cancel(int $id): BarcodePrintJob
    {
        $job = $this->getById($id);
        $job->cancel();

        return $this->jobs->save($job);
    }

    public function delete(int $id): void
    {
        $this->getById($id); // throws if not found
        $this->jobs->delete($id);
    }
}
