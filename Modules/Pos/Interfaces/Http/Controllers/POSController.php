<?php

declare(strict_types=1);

namespace Modules\POS\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\POS\Application\DTOs\CreatePOSTransactionDTO;
use Modules\POS\Application\Services\POSService;

/**
 * POS controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to POSService.
 *
 * @OA\Tag(name="POS", description="Point-of-sale transaction endpoints")
 */
class POSController extends Controller
{
    public function __construct(private readonly POSService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/pos/transactions",
     *     tags={"POS"},
     *     summary="Create a POS transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id","lines","payments"},
     *             @OA\Property(property="session_id", type="integer"),
     *             @OA\Property(property="discount_amount", type="string", example="0.0000"),
     *             @OA\Property(property="is_offline", type="boolean"),
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"product_id","uom_id","quantity","unit_price","discount_amount"},
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="uom_id", type="integer"),
     *                     @OA\Property(property="quantity", type="string", example="2.0000"),
     *                     @OA\Property(property="unit_price", type="string", example="15.0000"),
     *                     @OA\Property(property="discount_amount", type="string", example="0.0000")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="payments",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"payment_method","amount"},
     *                     @OA\Property(property="payment_method", type="string", enum={"cash","card","voucher","loyalty_points","gift_card"}),
     *                     @OA\Property(property="amount", type="string", example="30.0000"),
     *                     @OA\Property(property="reference", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="POS transaction created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id'                    => ['required', 'integer'],
            'discount_amount'               => ['nullable', 'numeric', 'min:0'],
            'is_offline'                    => ['nullable', 'boolean'],
            'lines'                         => ['required', 'array', 'min:1'],
            'lines.*.product_id'            => ['required', 'integer'],
            'lines.*.uom_id'                => ['required', 'integer'],
            'lines.*.quantity'              => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price'            => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount'       => ['required', 'numeric', 'min:0'],
            'payments'                      => ['required', 'array', 'min:1'],
            'payments.*.payment_method'     => ['required', 'string', 'in:cash,card,voucher,loyalty_points,gift_card'],
            'payments.*.amount'             => ['required', 'numeric', 'min:0'],
            'payments.*.reference'          => ['nullable', 'string'],
        ]);

        $dto         = CreatePOSTransactionDTO::fromArray($validated);
        $transaction = $this->service->createTransaction($dto);

        return ApiResponse::created($transaction->load(['lines', 'payments']), 'POS transaction created.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pos/transactions/{id}/void",
     *     tags={"POS"},
     *     summary="Void a POS transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Transaction voided"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function voidTransaction(int $id): JsonResponse
    {
        $transaction = $this->service->voidTransaction($id);

        return ApiResponse::success($transaction, 'POS transaction voided.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pos/sync",
     *     tags={"POS"},
     *     summary="Sync offline POS transactions",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_ids"},
     *             @OA\Property(property="transaction_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transactions synced"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function syncOfflineTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_ids'   => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['required', 'integer'],
        ]);

        $synced = $this->service->syncOfflineTransactions($validated['transaction_ids']);

        return ApiResponse::success(['synced_ids' => $synced], 'Offline transactions synced.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pos/transactions/{id}",
     *     tags={"POS"},
     *     summary="Show a single POS transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Transaction data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showTransaction(int $id): JsonResponse
    {
        $transaction = $this->service->showTransaction($id);

        return ApiResponse::success($transaction, 'POS transaction retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pos/sessions",
     *     tags={"POS"},
     *     summary="List all POS sessions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of POS sessions")
     * )
     */
    public function listSessions(): JsonResponse
    {
        $sessions = $this->service->listSessions();

        return ApiResponse::success($sessions, 'POS sessions retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pos/sessions",
     *     tags={"POS"},
     *     summary="Open a new POS session",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"terminal_id"},
     *             @OA\Property(property="terminal_id", type="integer"),
     *             @OA\Property(property="cashier_id", type="integer", nullable=true),
     *             @OA\Property(property="opening_float", type="string", example="0.0000")
     *         )
     *     ),
     *     @OA\Response(response=201, description="POS session opened"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function openSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'terminal_id'    => ['required', 'integer'],
            'cashier_id'     => ['nullable', 'integer'],
            'opening_float'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $session = $this->service->openSession(array_merge($validated, [
            'status'    => 'open',
            'opened_at' => now(),
        ]));

        return ApiResponse::created($session, 'POS session opened.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pos/sessions/{id}/close",
     *     tags={"POS"},
     *     summary="Close a POS session",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="POS session closed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function closeSession(int $id): JsonResponse
    {
        $session = $this->service->closeSession($id);

        return ApiResponse::success($session, 'POS session closed.');
    }
}
