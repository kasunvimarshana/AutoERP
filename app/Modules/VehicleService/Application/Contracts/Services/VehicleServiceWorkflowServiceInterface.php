<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleServiceWorkflowServiceInterface
{
    public function transition(int|string $jobCardId, array $payload): Result;

    public function createInvoice(int|string $jobCardId, array $payload): Result;

    public function allocatePayment(int|string $jobCardId, array $payload): Result;

    public function postInventory(int|string $jobCardId, array $payload): Result;

    public function postFinance(int|string $jobCardId, array $payload): Result;

    public function reverseFinance(int|string $jobCardId, array $payload): Result;
}
