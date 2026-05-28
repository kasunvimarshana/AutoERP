<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface CustomerManagementServiceInterface
{
    public function listCustomers(array $filters, int $perPage, int $page): Result;

    public function getCustomer(int|string $id): Result;

    public function createCustomer(array $payload): Result;

    public function updateCustomer(int|string $id, array $payload): Result;

    public function changeStatus(int|string $id, string $toStatus, ?string $reason = null): Result;

    public function safeDeleteCustomer(int|string $id): Result;

    public function lookupCustomers(string $search, int $limit = 20): Result;

    public function validateCustomerForSales(int|string $id): Result;

    public function validateCustomerForVehicleService(int|string $id): Result;

    public function validateCustomerForVehicleRental(int|string $id): Result;

    public function getFinanceDefaults(int|string $id): Result;

    public function updateFinanceDefaults(int|string $id, array $payload): Result;

    public function getCustomerTaxProfile(int|string $id): Result;

    public function checkCustomerCreditLimit(int|string $id, ?float $requestedAmount = null): Result;

    public function listCustomerUserAccounts(int|string $customerId): Result;

    public function createCustomerUserAccess(int|string $customerId, array $payload): Result;

    public function linkExistingUser(int|string $customerId, array $payload): Result;

    public function deactivateCustomerUserAccess(int|string $customerId, int|string $accessId, array $payload): Result;

    public function unlinkCustomerUserAccess(int|string $customerId, int|string $accessId): Result;
}
