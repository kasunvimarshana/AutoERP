<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentMapper;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Sales\Services\Concerns\CreatesFastSalesDocuments;
use Modules\Sales\Services\Concerns\PresentsFastSalesResults;
use Modules\Sales\Services\Concerns\ProvidesFastSalesLookups;
use Modules\Sales\Services\Concerns\ResolvesFastSales;
use Modules\Sales\Services\Concerns\ValidatesFastSales;
use Modules\Sales\Validators\SalesValidationService;
use Modules\Tax\Services\TaxCalculationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;
use Modules\Warehouse\Services\WarehouseDefaultResolver;

final class FastSalesService
{
    private const CUSTOMER_TYPE = 'customer';
    private const IDEMPOTENCY_OPERATION = 'sales.fast_sales';

    use ResolvesFastSales;
    use ValidatesFastSales;
    use CreatesFastSalesDocuments;
    use PresentsFastSalesResults;
    use ProvidesFastSalesLookups;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly StockAvailabilityService $stockAvailability,
        private readonly TaxCalculationService $taxes,
        private readonly TaxDocumentIntegrationService $taxDocuments,
        private readonly InvoiceTaxDocumentMapper $invoiceTaxDocuments,
        private readonly SalesOrderService $salesOrders,
        private readonly SalesDeliveryService $deliveries,
        private readonly SalesInvoiceIntegrationService $salesInvoices,
        private readonly SalesPaymentPreparationService $salesPayments,
        private readonly ItemPriceResolutionService $priceResolver,
        private readonly PaymentCreationService $payments,
        private readonly PaymentDocumentLifecycleService $paymentDocuments,
        private readonly PaymentPostingService $paymentPostings,
        private readonly FinancePostingInterface $financePostings,
        private readonly AuditRecorderInterface $audit,
        private readonly WarehouseDefaultResolver $warehouses,
        private readonly IdempotencyService $idempotency,
        private readonly FastSalesIdempotencyNormalizer $idempotencyNormalizer,
    ) {}

    /** @param array<string, mixed> $payload */
    public function context(array $payload): array
    {
        $tenantId = (int) $payload['tenant_id'];
        $organizationUnitId = $this->nullableInt($payload['organization_unit_id'] ?? null);
        $search = trim((string) ($payload['search'] ?? ''));
        $perPage = max(1, min(100, (int) ($payload['per_page'] ?? 25)));

        return [
            'defaults' => [
                'transaction_date' => now()->toDateString(),
                'exchange_rate' => '1.000000',
            ],
            'endpoints' => [
                'customer_search' => '/api/v1/customers/lookup/active',
                'item_search' => '/api/v1/items/lookup',
                'preview' => '/api/v1/sales/fast-sales/preview',
                'create' => '/api/v1/sales/fast-sales',
            ],
            'warehouses' => $this->warehouseOptions($tenantId, $organizationUnitId, $search, $perPage),
            'currencies' => $this->currencyOptions($search, $perPage),
            'payment_methods' => $this->paymentMethodOptions($tenantId, $organizationUnitId, $search, $perPage),
            'tax_groups' => $this->taxGroupOptions($tenantId, $organizationUnitId, $search, $perPage),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function preview(array $payload): array
    {
        $this->rejectClientAuthorityFields($payload);
        return $this->previewResponse($this->resolve($payload, lockRecords: false));
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        $this->rejectClientAuthorityFields($payload);
        return DB::transaction(function () use ($payload): array {
            $resolved = $this->resolve($payload, lockRecords: true);
            $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));
            if ($idempotencyKey === '') {
                throw new InvalidArgumentException('Fast sales requires an Idempotency-Key header or idempotency_key payload value.');
            }
            $referenceHash = hash('sha256', mb_strtolower($idempotencyKey));
            $requestHash = $this->idempotencyNormalizer->hash($payload);
            $idempotency = $this->idempotency->acquire(
                (int) $resolved['tenant_id'],
                $resolved['organization_unit_id'],
                self::IDEMPOTENCY_OPERATION,
                $referenceHash,
                $requestHash,
                $idempotencyKey,
                $resolved['current_user_id'],
            );
            if ($idempotency->status === IdempotencyStatus::Completed && is_array($idempotency->result)) {
                return $idempotency->result;
            }
            if (! $idempotency->wasRecentlyCreated && $idempotency->status === IdempotencyStatus::InProgress) {
                throw new InvalidArgumentException('Fast sales request is already in progress for this idempotency key.');
            }
            if ($idempotency->status !== IdempotencyStatus::InProgress) {
                throw new InvalidArgumentException('Fast sales idempotency record is not executable.');
            }
            $documents = $this->createDocuments($resolved);
            $response = $this->createResponse($resolved, $documents);
            $this->idempotency->complete($idempotency, $response, $this->documentIds($documents));
            $this->writeAuditLog($resolved, $referenceHash, $requestHash, $response);
            return $response;
        });
    }
}
