<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\FindBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankCategoryRuleServiceInterface;
use Modules\Finance\Domain\Entities\BankCategoryRule;
use Modules\Finance\Infrastructure\Http\Requests\ListBankCategoryRuleRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreBankCategoryRuleRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateBankCategoryRuleRequest;
use Modules\Finance\Infrastructure\Http\Resources\BankCategoryRuleCollection;
use Modules\Finance\Infrastructure\Http\Resources\BankCategoryRuleResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BankCategoryRuleController extends AuthorizedController
{
    public function __construct(
        private readonly CreateBankCategoryRuleServiceInterface $createService,
        private readonly UpdateBankCategoryRuleServiceInterface $updateService,
        private readonly DeleteBankCategoryRuleServiceInterface $deleteService,
        private readonly FindBankCategoryRuleServiceInterface $findService,
    ) {}

    public function index(ListBankCategoryRuleRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BankCategoryRule::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new BankCategoryRuleCollection($items))->response();
    }

    public function store(StoreBankCategoryRuleRequest $request): JsonResponse
    {
        $this->authorize('create', BankCategoryRule::class);

        $rule = $this->createService->execute($request->validated());

        return (new BankCategoryRuleResource($rule))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $bankCategoryRule): BankCategoryRuleResource
    {
        $found = $this->findOrFail($bankCategoryRule);
        $this->authorize('view', $found);

        return new BankCategoryRuleResource($found);
    }

    public function update(UpdateBankCategoryRuleRequest $request, int $bankCategoryRule): BankCategoryRuleResource
    {
        $found = $this->findOrFail($bankCategoryRule);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $bankCategoryRule;

        return new BankCategoryRuleResource($this->updateService->execute($payload));
    }

    public function destroy(int $bankCategoryRule): JsonResponse
    {
        $found = $this->findOrFail($bankCategoryRule);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $bankCategoryRule]);

        return Response::json(['message' => 'Bank category rule deleted successfully']);
    }

    private function findOrFail(int $id): BankCategoryRule
    {
        $rule = $this->findService->find($id);

        if (! $rule) {
            throw new NotFoundHttpException('Bank category rule not found.');
        }

        return $rule;
    }
}
