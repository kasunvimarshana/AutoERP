<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tax\Application\Contracts\FindTransactionTaxServiceInterface;
use Modules\Tax\Application\Contracts\RecordTransactionTaxesServiceInterface;
use Modules\Tax\Application\Contracts\ResolveTaxServiceInterface;
use Modules\Tax\Domain\Entities\TransactionTax;
use Modules\Tax\Infrastructure\Http\Requests\ListTransactionTaxRequest;
use Modules\Tax\Infrastructure\Http\Requests\RecordTransactionTaxesRequest;
use Modules\Tax\Infrastructure\Http\Requests\ResolveTaxRequest;
use Modules\Tax\Infrastructure\Http\Resources\TransactionTaxCollection;

class TaxCalculationController extends AuthorizedController
{
    public function __construct(
        protected ResolveTaxServiceInterface $resolveTaxService,
        protected RecordTransactionTaxesServiceInterface $recordTransactionTaxesService,
        protected FindTransactionTaxServiceInterface $findTransactionTaxService,
    ) {}

    public function resolve(ResolveTaxRequest $request): JsonResponse
    {
        $this->authorize('viewAny', TransactionTax::class);

        return Response::json([
            'data' => $this->resolveTaxService->execute($request->validated()),
        ]);
    }

    public function record(RecordTransactionTaxesRequest $request, string $referenceType, int $referenceId): JsonResponse
    {
        $this->authorize('create', TransactionTax::class);

        $payload = $request->validated();
        $payload['reference_type'] = $referenceType;
        $payload['reference_id'] = $referenceId;

        $recorded = $this->recordTransactionTaxesService->execute($payload);

        return Response::json([
            'data' => (new TransactionTaxCollection(collect($recorded)))->toArray($request),
            'message' => 'Transaction taxes recorded successfully',
        ]);
    }

    public function index(ListTransactionTaxRequest $request, string $referenceType, int $referenceId): JsonResponse
    {
        $this->authorize('viewAny', TransactionTax::class);

        $tenantId = (int) $request->validated()['tenant_id'];
        $transactionTaxes = $this->findTransactionTaxService->listByReference($tenantId, $referenceType, $referenceId);

        return Response::json([
            'data' => (new TransactionTaxCollection(collect($transactionTaxes)))->toArray($request),
        ]);
    }
}
