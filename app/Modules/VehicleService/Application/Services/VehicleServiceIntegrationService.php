<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceIntegrationServiceInterface;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceWorkflowServiceInterface;

final class VehicleServiceIntegrationService implements VehicleServiceIntegrationServiceInterface
{
    public function __construct(private readonly VehicleServiceWorkflowServiceInterface $workflowService)
    {
    }

    public function allocateServicePayment(int $jobCardId, array $payload): Result
    {
        return $this->workflowService->allocatePayment($jobCardId, $payload);
    }

    public function postServiceInventory(int $jobCardId, array $payload): Result
    {
        return $this->workflowService->postInventory($jobCardId, $payload);
    }
}
