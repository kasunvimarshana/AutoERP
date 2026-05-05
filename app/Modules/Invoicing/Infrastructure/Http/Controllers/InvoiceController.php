<?php

declare(strict_types=1);

namespace Modules\Invoicing\Infrastructure\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Invoicing\Application\Contracts\InvoiceServiceInterface;
use Modules\Invoicing\Application\DTOs\CreateInvoiceDTO;
use Modules\Invoicing\Application\DTOs\RecordInvoicePaymentDTO;
use Modules\Invoicing\Domain\Entities\Invoice;
use Modules\Invoicing\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoicing\Domain\ValueObjects\InvoiceEntityType;
use Modules\Invoicing\Domain\ValueObjects\InvoiceType;
use Modules\Invoicing\Infrastructure\Http\Requests\ChangeInvoiceStatusRequest;
use Modules\Invoicing\Infrastructure\Http\Requests\CreateInvoiceRequest;
use Modules\Invoicing\Infrastructure\Http\Requests\RecordInvoicePaymentRequest;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $orgUnitId = (string) $request->query('org_unit_id', $tenantId);

        $invoices = $this->service->listByTenant($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(fn (Invoice $invoice): array => $this->transform($invoice), $invoices)]);
    }

    public function byEntity(Request $request, string $entityType, string $entityId): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');

        $invoices = $this->service->listByEntity($tenantId, $entityType, $entityId);

        return response()->json(['data' => array_map(fn (Invoice $invoice): array => $this->transform($invoice), $invoices)]);
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $validated = $request->validated();

        $dto = new CreateInvoiceDTO(
            tenantId: $tenantId,
            orgUnitId: (string) ($validated['org_unit_id'] ?? $tenantId),
            invoiceNumber: $validated['invoice_number'],
            invoiceType: InvoiceType::from($validated['invoice_type']),
            entityType: InvoiceEntityType::from($validated['entity_type']),
            entityId: $validated['entity_id'] ?? null,
            issueDate: new DateTimeImmutable($validated['issue_date']),
            dueDate: new DateTimeImmutable($validated['due_date']),
            subtotalAmount: number_format((float) $validated['subtotal_amount'], 6, '.', ''),
            taxAmount: number_format((float) ($validated['tax_amount'] ?? 0), 6, '.', ''),
            totalAmount: number_format((float) $validated['total_amount'], 6, '.', ''),
            currency: strtoupper((string) ($validated['currency'] ?? 'USD')),
            notes: $validated['notes'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        $invoice = $this->service->create($dto);

        return response()->json(['data' => $this->transform($invoice)], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $invoice = $this->service->getById($id);

            return response()->json(['data' => $this->transform($invoice)]);
        } catch (InvoiceNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function changeStatus(ChangeInvoiceStatusRequest $request, string $id): JsonResponse
    {
        try {
            $invoice = $this->service->updateStatus($id, (string) $request->validated('status'));

            return response()->json(['data' => $this->transform($invoice)]);
        } catch (InvoiceNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function recordPayment(RecordInvoicePaymentRequest $request, string $id): JsonResponse
    {
        try {
            $invoice = $this->service->recordPayment(new RecordInvoicePaymentDTO(
                id: $id,
                amount: number_format((float) $request->validated('amount'), 6, '.', ''),
            ));

            return response()->json(['data' => $this->transform($invoice)]);
        } catch (InvoiceNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(null, 204);
        } catch (InvoiceNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    private function transform(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'tenant_id' => $invoice->tenantId,
            'org_unit_id' => $invoice->orgUnitId,
            'row_version' => $invoice->rowVersion,
            'invoice_number' => $invoice->invoiceNumber,
            'invoice_type' => $invoice->invoiceType->value,
            'entity_type' => $invoice->entityType->value,
            'entity_id' => $invoice->entityId,
            'status' => $invoice->status->value,
            'issue_date' => $invoice->issueDate->format('Y-m-d'),
            'due_date' => $invoice->dueDate->format('Y-m-d'),
            'subtotal_amount' => $invoice->subtotalAmount,
            'tax_amount' => $invoice->taxAmount,
            'total_amount' => $invoice->totalAmount,
            'paid_amount' => $invoice->paidAmount,
            'balance_amount' => $invoice->balanceAmount,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'metadata' => $invoice->metadata,
            'is_active' => $invoice->isActive,
            'created_at' => $invoice->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $invoice->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
