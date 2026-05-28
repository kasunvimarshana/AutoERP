<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleRentalIntegrationServiceInterface
{
    public function createRentalInvoice(int $agreementId, array $payload): Result;

    public function allocateRentalPayment(int $agreementId, array $payload): Result;

    public function createRentalProviderPayable(int $agreementId, array $payload): Result;

    public function allocateProviderPayablePayment(int $providerPayableId, array $payload): Result;
}
