<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Events\QuotationConverted;
use Modules\Sales\Events\QuotationSent;
use Modules\Sales\Http\Requests\StoreQuotationRequest;
use Modules\Sales\Http\Requests\UpdateQuotationRequest;
use Modules\Sales\Http\Resources\QuotationResource;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Repositories\QuotationRepository;
use Modules\Sales\Services\QuotationService;

class QuotationController extends Controller
{
    public function __construct(
        private QuotationRepository $quotationRepository,
        private QuotationService $quotationService
    ) {}

    /**
     * Display a listing of quotations.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Quotation::class);

        $filters = [
            'status' => $request->input('status'),
            'customer_id' => $request->input('customer_id'),
            'organization_id' => $request->input('organization_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'search' => $request->input('search'),
        ];

        $perPage = $request->get('per_page', 15);
        $quotations = $this->quotationRepository->getFiltered($filters, $perPage);

        return ApiResponse::paginated(
            $quotations->setCollection(
                $quotations->getCollection()->map(fn ($quotation) => new QuotationResource($quotation))
            ),
            'Quotations retrieved successfully'
        );
    }

    /**
     * Store a newly created quotation.
     */
    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $this->authorize('create', Quotation::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $items = $data['items'] ?? [];
        unset($data['items']);

        $quotation = $this->quotationService->createQuotation($data, $items);
        $quotation->load(['organization', 'customer', 'items.product']);

        return ApiResponse::created(
            new QuotationResource($quotation),
            'Quotation created successfully'
        );
    }

    /**
     * Display the specified quotation.
     */
    public function show(Quotation $quotation): JsonResponse
    {
        $this->authorize('view', $quotation);

        $quotation->load(['organization', 'customer', 'items.product', 'convertedOrder']);

        return ApiResponse::success(
            new QuotationResource($quotation),
            'Quotation retrieved successfully'
        );
    }

    /**
     * Update the specified quotation.
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        $data = $request->validated();
        $quotation = $this->quotationService->updateQuotation($quotation->id, $data);
        $quotation->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new QuotationResource($quotation),
            'Quotation updated successfully'
        );
    }

    /**
     * Remove the specified quotation.
     */
    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->authorize('delete', $quotation);
        $this->quotationService->deleteQuotation($quotation->id);

        return ApiResponse::success(
            null,
            'Quotation deleted successfully'
        );
    }

    /**
     * Send the quotation to the customer.
     */
    public function send(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        if (! $quotation->status->canSend()) {
            return ApiResponse::error(
                'Quotation cannot be sent in its current status',
                422
            );
        }

        $quotation = $this->quotationService->sendQuotation($quotation->id);
        event(new QuotationSent($quotation));
        $quotation->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new QuotationResource($quotation),
            'Quotation sent successfully'
        );
    }

    /**
     * Accept the quotation.
     */
    public function accept(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        if (! in_array($quotation->status, [QuotationStatus::SENT, QuotationStatus::DRAFT])) {
            return ApiResponse::error(
                'Quotation cannot be accepted in its current status',
                422
            );
        }

        $quotation = $this->quotationService->acceptQuotation($quotation->id);
        $quotation->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new QuotationResource($quotation),
            'Quotation accepted successfully'
        );
    }

    /**
     * Reject the quotation.
     */
    public function reject(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        if (! in_array($quotation->status, [QuotationStatus::SENT, QuotationStatus::DRAFT])) {
            return ApiResponse::error(
                'Quotation cannot be rejected in its current status',
                422
            );
        }

        $quotation = $this->quotationService->rejectQuotation($quotation->id);
        $quotation->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new QuotationResource($quotation),
            'Quotation rejected successfully'
        );
    }

    /**
     * Convert the quotation to an order.
     */
    public function convertToOrder(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        if (! $quotation->status->canConvert()) {
            return ApiResponse::error(
                'Only accepted quotations can be converted to orders',
                422
            );
        }

        $result = $this->quotationService->convertToOrder($quotation->id);
        $quotation = $result['quotation'];
        $order = $result['order'];
        event(new QuotationConverted($quotation, $order));
        $quotation->load(['organization', 'customer', 'items.product', 'convertedOrder']);

        return ApiResponse::success(
            [
                'quotation' => new QuotationResource($quotation),
                'order_id' => $order->id,
                'order_code' => $order->order_code,
            ],
            'Quotation converted to order successfully'
        );
    }
}
