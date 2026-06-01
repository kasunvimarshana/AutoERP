<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\PurchaseOrders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\GenerateSequenceNumberServiceInterface;
use Throwable;

final class CreatePurchaseOrderService implements CreatePurchaseOrderServiceInterface
{
    private const DOCUMENT_TYPE = 'PURCHASE_ORDER';

    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly GenerateSequenceNumberServiceInterface $sequenceGenerator,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            return $this->repository->transaction(function () use ($payload): Result {
                $numberedPayload = $this->withGeneratedPurchaseOrderNumber($payload);
                if ($numberedPayload->isFailure()) {
                    return $numberedPayload;
                }

                $payload = $numberedPayload->valueOrFail();
                if (! array_key_exists('row_version', $payload)) {
                    $payload['row_version'] = 1;
                }

                return Result::success($this->repository->create($payload));
            });
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'Unable to create purchase order. Please check the submitted fields and purchase order sequence settings.',
            ));
        }
    }

    /**
     * @return Result<array<string, mixed>>
     */
    private function withGeneratedPurchaseOrderNumber(array $payload): Result
    {
        unset($payload['po_number']);

        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        if ($tenantId < 1) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'tenant_id is required to generate a purchase order number.'));
        }

        $organizationUnitId = array_key_exists('organization_unit_id', $payload) && $payload['organization_unit_id'] !== null
            ? (int) $payload['organization_unit_id']
            : null;

        $sequence = $this->sequenceGenerator->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => self::DOCUMENT_TYPE,
            'prefix' => $organizationUnitId !== null ? 'PO-{ORG_ID}-' : 'PO-',
            'padding' => 6,
            'at_date' => $payload['order_date'] ?? null,
            'metadata' => ['source' => 'purchase_order'],
        ]);

        if ($sequence->isFailure()) {
            return Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'Unable to generate purchase order number. Check the Purchase Order sequence settings.',
            ));
        }

        $generatedNumber = (string) (($sequence->valueOrFail()['generated_number'] ?? '') ?: '');
        if ($generatedNumber === '') {
            return Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'Unable to generate purchase order number. Check the Purchase Order sequence settings.',
            ));
        }

        $payload['po_number'] = $generatedNumber;

        return Result::success($payload);
    }
}
