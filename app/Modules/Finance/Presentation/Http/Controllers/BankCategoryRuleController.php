<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\CreateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\DeleteBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\GetBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\ListBankCategoryRulesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\UpdateBankCategoryRuleServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListBankCategoryRuleRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertBankCategoryRuleRequest;
use Modules\Finance\Presentation\Http\Resources\BankCategoryRuleResource;

final class BankCategoryRuleController extends Controller
{
    public function __construct(
        private readonly ListBankCategoryRulesServiceInterface $listService,
        private readonly GetBankCategoryRuleServiceInterface $getService,
        private readonly CreateBankCategoryRuleServiceInterface $createService,
        private readonly UpdateBankCategoryRuleServiceInterface $updateService,
        private readonly DeleteBankCategoryRuleServiceInterface $deleteService,
    ) {
    }

    public function index(ListBankCategoryRuleRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['tenant_id'])) {
            $criteria['tenant_id'] = (int) $validated['tenant_id'];
        }

        if (isset($validated['organization_unit_id'])) {
            $criteria['organization_unit_id'] = (int) $validated['organization_unit_id'];
        }

        if (isset($validated['search'])) {
            $search = trim((string) $validated['search']);
            if ($search !== '') {
                $criteria['search'] = $search;
            }
        }

        if (array_key_exists('bank_account_id', $validated) && $validated['bank_account_id'] !== null) {
            $criteria['bank_account_id'] = $validated['bank_account_id'];
        }

        if (array_key_exists('account_id', $validated) && $validated['account_id'] !== null) {
            $criteria['account_id'] = $validated['account_id'];
        }

        if (array_key_exists('match_type', $validated) && $validated['match_type'] !== null) {
            $criteria['match_type'] = $validated['match_type'];
        }

        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $criteria['is_active'] = $validated['is_active'];
        }

        $result = $this->listService->execute(
            $criteria,
            (int) ($validated['per_page'] ?? 0),
            (int) ($validated['page'] ?? 0),
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => BankCategoryRuleResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BankCategoryRuleResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BankCategoryRuleResource($result->valueOrFail());
    }

    public function store(UpsertBankCategoryRuleRequest $request): JsonResponse|BankCategoryRuleResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BankCategoryRuleResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBankCategoryRuleRequest $request, int|string $id): JsonResponse|BankCategoryRuleResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BankCategoryRuleResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
