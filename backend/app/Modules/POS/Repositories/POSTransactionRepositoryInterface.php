<?php

declare(strict_types=1);

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\POSTransaction;
use Illuminate\Database\Eloquent\Collection;

interface POSTransactionRepositoryInterface
{
    public function findByTransactionNumber(string $transactionNumber): ?POSTransaction;
    public function getBySession(int $sessionId): Collection;
    public function getByCustomer(int $customerId): Collection;
    public function getByDateRange(string $startDate, string $endDate): Collection;
    public function getTotalBySession(int $sessionId): array;
}
