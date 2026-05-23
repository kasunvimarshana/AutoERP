<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Supplier\Application\Services\SupplierService;
use Modules\Supplier\Domain\Exceptions\SupplierIntegrityException;
use Modules\Supplier\Domain\Exceptions\SupplierRecordNotFoundException;
use Modules\Supplier\Presentation\Http\Resources\SupplierRecordResource;

class SupplierLifecycleController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers) {}

    public function primaryContact(int|string $tenant, int|string $contact): SupplierRecordResource|JsonResponse
    {
        try {
            return new SupplierRecordResource($this->suppliers->setPrimaryContact($tenant, $contact));
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function defaultAddress(int|string $tenant, int|string $address): SupplierRecordResource|JsonResponse
    {
        try {
            return new SupplierRecordResource($this->suppliers->setDefaultAddress($tenant, $address));
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function currentVehicle(int|string $tenant, int|string $vehicle): SupplierRecordResource|JsonResponse
    {
        try {
            return new SupplierRecordResource($this->suppliers->setCurrentVehicle($tenant, $vehicle));
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function preferredItem(int|string $tenant, int|string $item): SupplierRecordResource|JsonResponse
    {
        try {
            return new SupplierRecordResource($this->suppliers->setPreferredItem($tenant, $item));
        } catch (SupplierIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (SupplierRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    private function notFound(SupplierRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(SupplierIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
