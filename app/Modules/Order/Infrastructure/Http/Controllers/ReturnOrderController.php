<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Order\Application\Contracts\ReturnOrderServiceInterface;
use Modules\Order\Application\DTOs\ReturnOrderData;
use Modules\Order\Infrastructure\Http\Resources\ReturnOrderResource;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\ReturnOrderModel;

class ReturnOrderController extends BaseController
{
    public function __construct(ReturnOrderServiceInterface $service)
    {
        parent::__construct($service, ReturnOrderResource::class, ReturnOrderData::class);
    }

    protected function getModelClass(): string
    {
        return ReturnOrderModel::class;
    }

    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'type']));
        $paginator = $this->service->list($filters, $request->integer('per_page', 15));

        return ReturnOrderResource::collection($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\ReturnOrderServiceInterface $service */
        $service = $this->service;
        $return = $service->createReturnOrder($request->all());

        return (new ReturnOrderResource($return))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        return (new ReturnOrderResource($this->service->find($id)))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return (new ReturnOrderResource($this->service->update($id, $request->all())))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    public function confirm(string $id): JsonResponse
    {
        /** @var \Modules\Order\Application\Contracts\ReturnOrderServiceInterface $service */
        $service = $this->service;

        return (new ReturnOrderResource($service->confirmReturn($id)))->response();
    }
}
