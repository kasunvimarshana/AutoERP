<?php

namespace Modules\Accounting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Accounting\Application\UseCases\IssueCreditNoteUseCase;
use Modules\Accounting\Application\UseCases\PostInvoiceUseCase;
use Modules\Accounting\Application\UseCases\RecordPaymentUseCase;
use Modules\Accounting\Infrastructure\Repositories\InvoiceRepository;
use Modules\Accounting\Presentation\Requests\IssueCreditNoteRequest;
use Modules\Accounting\Presentation\Requests\StoreInvoiceRequest;
use Modules\Shared\Application\ResponseFormatter;

class InvoiceController extends Controller
{
    public function __construct(
        private CreateInvoiceUseCase   $createUseCase,
        private PostInvoiceUseCase     $postUseCase,
        private RecordPaymentUseCase   $recordPaymentUseCase,
        private IssueCreditNoteUseCase $issueCreditNoteUseCase,
        private InvoiceRepository      $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($invoice, 'Invoice created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $invoice = $this->repo->findById($id);
        if (! $invoice) {
            return ResponseFormatter::error('Invoice not found.', [], 404);
        }
        return ResponseFormatter::success($invoice);
    }

    public function post(string $id): JsonResponse
    {
        try {
            $invoice = $this->postUseCase->execute(['id' => $id]);
            return ResponseFormatter::success($invoice, 'Invoice posted.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function recordPayment(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.00000001'],
            'payment_date' => ['required', 'date'],
            'reference'    => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $invoice = $this->recordPaymentUseCase->execute(array_merge(
                $request->only(['amount', 'payment_date', 'reference']),
                ['invoice_id' => $id]
            ));
            return ResponseFormatter::success($invoice, 'Payment recorded.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Invoice deleted.');
    }

    public function issueCreditNote(IssueCreditNoteRequest $request, string $id): JsonResponse
    {
        try {
            $creditNote = $this->issueCreditNoteUseCase->execute(array_merge(
                $request->validated(),
                ['source_invoice_id' => $id, 'tenant_id' => $request->input('tenant_id')]
            ));
            return ResponseFormatter::success($creditNote, 'Credit note issued.', 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 404);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
