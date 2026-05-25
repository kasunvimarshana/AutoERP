<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\CreateTaxRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\DeleteTaxRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\GetTaxRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\ListTaxRulesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\UpdateTaxRuleServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListTaxRuleRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertTaxRuleRequest;
use Modules\Finance\Presentation\Http\Resources\TaxRuleResource;

final class TaxRuleController extends Controller
{
    public function __construct(
        private readonly ListTaxRulesServiceInterface $listService,
        private readonly GetTaxRuleServiceInterface $getService,
        private readonly CreateTaxRuleServiceInterface $createService,
        private readonly UpdateTaxRuleServiceInterface $updateService,
        private readonly DeleteTaxRuleServiceInterface $deleteService,
    ) {
    }

    public function index(ListTaxRuleRequest $request): JsonResponse
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

        if (array_key_exists('tax_group_id', $validated) && $validated['tax_group_id'] !== null) {
            $criteria['tax_group_id'] = $validated['tax_group_id'];
        }

        if (array_key_exists('item_category_id', $validated) && $validated['item_category_id'] !== null) {
            $criteria['item_category_id'] = $validated['item_category_id'];
        }

        if (array_key_exists('party_type', $validated) && $validated['party_type'] !== null) {
            $criteria['party_type'] = $validated['party_type'];
        }

        if (array_key_exists('region', $validated) && $validated['region'] !== null) {
            $criteria['region'] = $validated['region'];
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
            'data' => TaxRuleResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|TaxRuleResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TaxRuleResource($result->valueOrFail());
    }

    public function store(UpsertTaxRuleRequest $request): JsonResponse|TaxRuleResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TaxRuleResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTaxRuleRequest $request, int|string $id): JsonResponse|TaxRuleResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TaxRuleResource($result->valueOrFail());
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
