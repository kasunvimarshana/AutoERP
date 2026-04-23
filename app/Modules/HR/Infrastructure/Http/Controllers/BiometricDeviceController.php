<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\DeleteBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\FindBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\SyncBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UpdateBiometricDeviceServiceInterface;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Infrastructure\Http\Requests\StoreBiometricDeviceRequest;
use Modules\HR\Infrastructure\Http\Requests\SyncBiometricDeviceRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateBiometricDeviceRequest;
use Modules\HR\Infrastructure\Http\Resources\BiometricDeviceResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BiometricDeviceController extends AuthorizedController
{
    public function __construct(
        protected CreateBiometricDeviceServiceInterface $createService,
        protected UpdateBiometricDeviceServiceInterface $updateService,
        protected DeleteBiometricDeviceServiceInterface $deleteService,
        protected FindBiometricDeviceServiceInterface $findService,
        protected SyncBiometricDeviceServiceInterface $syncService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => BiometricDeviceResource::collection($result)]);
    }

    public function store(StoreBiometricDeviceRequest $request): JsonResponse
    {
        $entity = $this->createService->execute($request->validated());

        return (new BiometricDeviceResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $biometricDevice): BiometricDeviceResource
    {
        return new BiometricDeviceResource($this->findOrFail($biometricDevice));
    }

    public function update(UpdateBiometricDeviceRequest $request, int $biometricDevice): BiometricDeviceResource
    {
        $this->findOrFail($biometricDevice);
        $payload = $request->validated();
        $payload['id'] = $biometricDevice;
        $updated = $this->updateService->execute($payload);

        return new BiometricDeviceResource($updated);
    }

    public function destroy(int $biometricDevice): JsonResponse
    {
        $this->findOrFail($biometricDevice);
        $this->deleteService->execute(['id' => $biometricDevice]);

        return Response::json(null, 204);
    }

    public function sync(SyncBiometricDeviceRequest $request, int $biometricDevice): JsonResponse
    {
        $this->findOrFail($biometricDevice);
        $payload = $request->validated();
        $payload['device_id'] = $biometricDevice;
        $this->syncService->execute($payload);

        return Response::json(['message' => 'Device sync initiated.']);
    }

    private function findOrFail(int $id): BiometricDevice
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Biometric device not found.');
        }

        return $entity;
    }
}
