<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\GS1\Application\Contracts\CreateGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\DeleteGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\FindGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\UpdateGs1BarcodeServiceInterface;
use Modules\GS1\Application\DTOs\Gs1BarcodeData;
use Modules\GS1\Application\DTOs\UpdateGs1BarcodeData;
use Modules\GS1\Infrastructure\Http\Requests\StoreGs1BarcodeRequest;
use Modules\GS1\Infrastructure\Http\Requests\UpdateGs1BarcodeRequest;
use Modules\GS1\Infrastructure\Http\Resources\Gs1BarcodeCollection;
use Modules\GS1\Infrastructure\Http\Resources\Gs1BarcodeResource;

class Gs1BarcodeController extends AuthorizedController
{
    public function __construct(
        protected FindGs1BarcodeServiceInterface $findService,
        protected CreateGs1BarcodeServiceInterface $createService,
        protected UpdateGs1BarcodeServiceInterface $updateService,
        protected DeleteGs1BarcodeServiceInterface $deleteService,
    ) {}

    public function index(Request $request): Gs1BarcodeCollection
    {
        $filters = $request->only(['tenant_id', 'gs1_identifier_id', 'barcode_type', 'is_primary', 'is_active']);

        return new Gs1BarcodeCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreGs1BarcodeRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = Gs1BarcodeData::fromArray([
            'tenantId'               => $v['tenant_id'],
            'gs1IdentifierId'        => $v['gs1_identifier_id'],
            'barcodeType'            => $v['barcode_type'],
            'barcodeData'            => $v['barcode_data'],
            'applicationIdentifiers' => $v['application_identifiers'] ?? null,
            'isPrimary'              => $v['is_primary'] ?? false,
            'isActive'               => $v['is_active'] ?? true,
            'metadata'               => $v['metadata'] ?? null,
        ]);

        $barcode = $this->createService->execute($dto->toArray());

        return (new Gs1BarcodeResource($barcode))->response()->setStatusCode(201);
    }

    public function show(int $id): Gs1BarcodeResource|JsonResponse
    {
        $barcode = $this->findService->find($id);
        if (! $barcode) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new Gs1BarcodeResource($barcode);
    }

    public function update(UpdateGs1BarcodeRequest $request, int $id): Gs1BarcodeResource
    {
        $v   = $request->validated();
        $dto = UpdateGs1BarcodeData::fromArray(array_merge(['id' => $id], $v));

        return new Gs1BarcodeResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'GS1 barcode deleted successfully']);
    }
}
