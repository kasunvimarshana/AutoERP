<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\UpdateServiceReturnServiceInterface;
use Modules\Service\Domain\Entities\ServiceReturn;
use Modules\Service\Domain\RepositoryInterfaces\ServiceReturnRepositoryInterface;
use RuntimeException;

class UpdateServiceReturnService extends BaseService implements UpdateServiceReturnServiceInterface
{
    public function __construct(private readonly ServiceReturnRepositoryInterface $returnRepository) {}

    protected function handle(array $data): ServiceReturn
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->returnRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new RuntimeException("Service return {$id} not found.");
        }

        if (in_array($existing->getStatus(), ['completed', 'cancelled'], true)) {
            throw new RuntimeException("Cannot update a {$existing->getStatus()} return.");
        }

        $newStatus = $data['status'] ?? $existing->getStatus();
        $processedAt = $existing->getProcessedAt();
        if ($newStatus === 'completed' && $existing->getProcessedAt() === null) {
            $processedAt = now()->toIso8601String();
        }

        $updated = new ServiceReturn(
            tenantId: $existing->getTenantId(),
            serviceWorkOrderId: $existing->getServiceWorkOrderId(),
            returnNumber: $existing->getReturnNumber(),
            returnType: $data['return_type'] ?? $existing->getReturnType(),
            status: $newStatus,
            orgUnitId: $existing->getOrgUnitId(),
            reasonCode: $data['reason_code'] ?? $existing->getReasonCode(),
            processedBy: isset($data['processed_by']) ? (int) $data['processed_by'] : $existing->getProcessedBy(),
            processedAt: $processedAt,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : $existing->getCurrencyId(),
            totalAmount: isset($data['total_amount']) ? (float) $data['total_amount'] : $existing->getTotalAmount(),
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : $existing->getJournalEntryId(),
            paymentId: isset($data['payment_id']) ? (int) $data['payment_id'] : $existing->getPaymentId(),
            notes: $data['notes'] ?? $existing->getNotes(),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->returnRepository->save($updated);
    }
}
