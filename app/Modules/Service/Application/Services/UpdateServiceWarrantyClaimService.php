<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\UpdateServiceWarrantyClaimServiceInterface;
use Modules\Service\Domain\Entities\ServiceWarrantyClaim;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWarrantyClaimRepositoryInterface;
use RuntimeException;

class UpdateServiceWarrantyClaimService extends BaseService implements UpdateServiceWarrantyClaimServiceInterface
{
    public function __construct(private readonly ServiceWarrantyClaimRepositoryInterface $claimRepository) {}

    protected function handle(array $data): ServiceWarrantyClaim
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->claimRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new RuntimeException("Service warranty claim {$id} not found.");
        }

        $newStatus = $data['status'] ?? $existing->getStatus();

        if ($existing->getStatus() === 'settled' && $newStatus !== 'settled') {
            throw new RuntimeException('Cannot revert a settled warranty claim.');
        }

        $submittedAt = $existing->getSubmittedAt();
        if ($newStatus === 'submitted' && $existing->getStatus() === 'draft') {
            $submittedAt = now()->toIso8601String();
        }

        $resolvedAt = $existing->getResolvedAt();
        if (in_array($newStatus, ['approved', 'rejected', 'settled'], true) && $existing->getResolvedAt() === null) {
            $resolvedAt = now()->toIso8601String();
        }

        $updated = new ServiceWarrantyClaim(
            tenantId: $existing->getTenantId(),
            serviceWorkOrderId: $existing->getServiceWorkOrderId(),
            warrantyProvider: $data['warranty_provider'] ?? $existing->getWarrantyProvider(),
            orgUnitId: $existing->getOrgUnitId(),
            supplierId: isset($data['supplier_id']) ? (int) $data['supplier_id'] : $existing->getSupplierId(),
            claimNumber: $data['claim_number'] ?? $existing->getClaimNumber(),
            status: $newStatus,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : $existing->getCurrencyId(),
            claimAmount: isset($data['claim_amount']) ? (float) $data['claim_amount'] : $existing->getClaimAmount(),
            approvedAmount: isset($data['approved_amount']) ? (float) $data['approved_amount'] : $existing->getApprovedAmount(),
            receivedAmount: isset($data['received_amount']) ? (float) $data['received_amount'] : $existing->getReceivedAmount(),
            submittedAt: $submittedAt,
            resolvedAt: $resolvedAt,
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : $existing->getJournalEntryId(),
            notes: $data['notes'] ?? $existing->getNotes(),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->claimRepository->save($updated);
    }
}
