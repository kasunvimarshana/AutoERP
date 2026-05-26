<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Orchestrators;

use Modules\VehicleService\Application\DTOs\CompleteJobCardDTO;
use Modules\VehicleService\Application\DTOs\CreateJobCardDTO;
use Modules\VehicleService\Application\DTOs\CreateServiceInvoiceDTO;
use Modules\VehicleService\Application\DTOs\CreateServicePaymentDTO;
use Modules\VehicleService\Domain\Services\VehicleServiceLifecycleService;

final class VehicleServiceOrchestrator
{
    public function __construct(private readonly VehicleServiceLifecycleService $lifecycle)
    {
    }

    public function start(int $jobCardId): array
    {
        return $this->lifecycle->open($jobCardId);
    }

    public function create(CreateJobCardDTO $dto): array
    {
        return $this->lifecycle->create($dto->payload);
    }

    public function complete(CompleteJobCardDTO $dto): array
    {
        return $this->lifecycle->complete($dto->jobCardId, $dto->payload);
    }

    public function invoice(CreateServiceInvoiceDTO $dto): array
    {
        return $this->lifecycle->createInvoice($dto->jobCardId);
    }

    public function payment(CreateServicePaymentDTO $dto): array
    {
        return $this->lifecycle->createPayment($dto->payload);
    }
}
