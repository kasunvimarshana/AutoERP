<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Sales\QuoteResource;
use App\Modules\Sales\Services\QuoteService;
use App\Http\Requests\Sales\StoreQuoteRequest;
use App\Http\Requests\Sales\UpdateQuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuoteController extends BaseController
{
    protected QuoteService $quoteService;

    public function __construct(QuoteService $quoteService)
    {
        $this->quoteService = $quoteService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $quotes = $this->quoteService->getAll([], $perPage);

            return $this->paginatedResponse(
                QuoteResource::collection($quotes),
                'Quotes retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve quotes: ' . $e->getMessage());
        }
    }

    public function store(StoreQuoteRequest $request): JsonResponse
    {
        try {
            $quote = $this->quoteService->createQuoteWithItems($request->validated());

            return $this->successResponse(
                new QuoteResource($quote),
                'Quote created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create quote: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $quote = $this->quoteService->findOrFail($id);
            return $this->successResponse(new QuoteResource($quote));
        } catch (\Exception $e) {
            return $this->errorResponse('Quote not found', 404);
        }
    }

    public function update(UpdateQuoteRequest $request, int $id): JsonResponse
    {
        try {
            $quote = $this->quoteService->updateQuoteWithItems($id, $request->validated());
            return $this->successResponse(new QuoteResource($quote), 'Quote updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update quote: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->quoteService->delete($id);
            return $this->successResponse(null, 'Quote deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete quote: ' . $e->getMessage());
        }
    }

    public function convertToOrder(int $id): JsonResponse
    {
        try {
            $quote = $this->quoteService->convertToSalesOrder($id);
            return $this->successResponse(new QuoteResource($quote), 'Quote marked for conversion');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to convert quote: ' . $e->getMessage());
        }
    }
}
