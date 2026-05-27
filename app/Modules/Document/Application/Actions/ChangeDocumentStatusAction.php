<?php

namespace Modules\Document\Application\Actions;

use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Exceptions\InvalidDocumentStatusTransitionException;
use Modules\Document\Domain\Repositories\DocumentWorkflowRepositoryInterface;

class ChangeDocumentStatusAction
{
    public function __construct(
        private readonly DocumentWorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function execute(DocumentAggregate $aggregate, string $toStatus, ?string $actionName = null): DocumentAggregate
    {
        $workflow = $this->workflowRepository->findActive(
            $aggregate->document->tenantId,
            $aggregate->document->documentTypeId,
        );

        $currentStatus = $aggregate->document->status;

        if ($workflow !== null) {
            foreach ($workflow->transitions as $transition) {
                if (
                    $transition['from'] === $currentStatus
                    && $transition['to'] === $toStatus
                    && ($actionName === null || $transition['action_name'] === $actionName)
                ) {
                    $aggregate->document->status = $toStatus;

                    return $aggregate;
                }
            }

            throw new InvalidDocumentStatusTransitionException("Transition [{$currentStatus} -> {$toStatus}] is not configured.");
        }

        $allowed = config('document.default_status_transitions')[$currentStatus] ?? [];

        if (! in_array($toStatus, $allowed, true)) {
            throw new InvalidDocumentStatusTransitionException("Transition [{$currentStatus} -> {$toStatus}] is not allowed.");
        }

        $aggregate->document->status = $toStatus;

        return $aggregate;
    }
}
