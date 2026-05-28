<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Services;

use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalIntegrationServiceInterface;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalWorkflowServiceInterface;

final class VehicleRentalIntegrationService implements VehicleRentalIntegrationServiceInterface
{
    public function __construct(private readonly VehicleRentalWorkflowServiceInterface $workflowService)
    {
    }

    public function createRentalInvoice(int $agreementId, array $payload): Result
    {
        return $this->workflowService->createInvoice($agreementId, $payload);
    }

    public function allocateRentalPayment(int $agreementId, array $payload): Result
    {
        return $this->workflowService->allocateCustomerPayment($agreementId, $payload);
    }

    public function createRentalProviderPayable(int $agreementId, array $payload): Result
    {
        return $this->workflowService->createProviderPayable($agreementId, $payload);
    }

    public function allocateProviderPayablePayment(int $providerPayableId, array $payload): Result
    {
        return $this->workflowService->allocateProviderPayment($providerPayableId, $payload);
    }
}
