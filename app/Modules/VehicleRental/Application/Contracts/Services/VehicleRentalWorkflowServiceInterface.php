<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleRentalWorkflowServiceInterface
{
    public function transitionAgreement(int|string $agreementId, array $payload): Result;

    public function transitionRunningChart(int|string $runningChartId, array $payload): Result;

    public function createInvoice(int|string $agreementId, array $payload): Result;

    public function allocateCustomerPayment(int|string $agreementId, array $payload): Result;

    public function createProviderPayable(int|string $agreementId, array $payload): Result;

    public function allocateProviderPayment(int|string $providerPayableId, array $payload): Result;

    public function postFinance(string $entityType, int|string $entityId, array $payload): Result;

    public function reverseFinance(string $entityType, int|string $entityId, array $payload): Result;
}
