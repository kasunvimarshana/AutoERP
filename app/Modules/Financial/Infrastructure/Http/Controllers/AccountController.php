<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Financial\Application\Contracts\AccountServiceInterface;
use Modules\Financial\Application\DTOs\AccountData;
use Modules\Financial\Infrastructure\Http\Resources\AccountResource;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\AccountModel;

class AccountController extends BaseController
{
    public function __construct(AccountServiceInterface $service)
    {
        parent::__construct($service, AccountResource::class, AccountData::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return AccountModel::class;
    }

    /**
     * List accounts with optional type/status filters.
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['type', 'sub_type', 'is_active', 'parent_id']));
        $paginator = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->integer('page', 1),
            $request->input('sort'),
            $request->input('include'),
        );

        return AccountResource::collection($paginator);
    }

    /**
     * Create a new account.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Financial\Application\Contracts\AccountServiceInterface $service */
        $service = $this->service;
        $account = $service->createAccount($request->all());

        return (new AccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single account.
     */
    public function show(string $id): JsonResponse
    {
        $account = $this->service->find($id);

        return (new AccountResource($account))->response();
    }

    /**
     * Update an account.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        /** @var \Modules\Financial\Application\Contracts\AccountServiceInterface $service */
        $service = $this->service;
        $account = $service->updateAccount($id, $request->all());

        return (new AccountResource($account))->response();
    }

    /**
     * Delete an account (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
