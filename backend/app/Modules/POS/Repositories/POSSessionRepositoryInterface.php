<?php

declare(strict_types=1);

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\POSSession;
use Illuminate\Database\Eloquent\Collection;

interface POSSessionRepositoryInterface
{
    public function findBySessionNumber(string $sessionNumber): ?POSSession;
    public function getOpenSessions(): Collection;
    public function getSessionsByCashier(int $cashierId): Collection;
    public function getSessionsByDateRange(string $startDate, string $endDate): Collection;
    public function getCurrentOpenSession(int $cashierId, ?int $terminalId = null): ?POSSession;
}
