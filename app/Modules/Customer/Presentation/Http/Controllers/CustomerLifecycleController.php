<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Customer\Application\Services\CustomerService;
use Modules\Customer\Domain\Exceptions\CustomerIntegrityException;
use Modules\Customer\Domain\Exceptions\CustomerRecordNotFoundException;
use Modules\Customer\Presentation\Http\Resources\CustomerRecordResource;

class CustomerLifecycleController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function primaryContact(int|string $tenant, int|string $contact): CustomerRecordResource|JsonResponse
    {
        try {
            return new CustomerRecordResource($this->customers->setPrimaryContact($tenant, $contact));
        } catch (CustomerIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function defaultAddress(int|string $tenant, int|string $address): CustomerRecordResource|JsonResponse
    {
        try {
            return new CustomerRecordResource($this->customers->setDefaultAddress($tenant, $address));
        } catch (CustomerIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function currentVehicle(int|string $tenant, int|string $vehicle): CustomerRecordResource|JsonResponse
    {
        try {
            return new CustomerRecordResource($this->customers->setCurrentVehicle($tenant, $vehicle));
        } catch (CustomerIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (CustomerRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    private function notFound(CustomerRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(CustomerIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
