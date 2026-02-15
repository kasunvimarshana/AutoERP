<?php

declare(strict_types=1);

namespace App\Modules\POS\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\POS\Models\POSSession;
use Illuminate\Database\Eloquent\Collection;

class POSSessionRepository extends BaseRepository implements POSSessionRepositoryInterface
{
    public function __construct(POSSession $model)
    {
        parent::__construct($model);
    }

    public function findBySessionNumber(string $sessionNumber): ?POSSession
    {
        return $this->model->where('session_number', $sessionNumber)->first();
    }

    public function getOpenSessions(): Collection
    {
        return $this->model->where('status', 'open')
            ->with(['cashier', 'transactions'])
            ->orderBy('opened_at', 'desc')
            ->get();
    }

    public function getSessionsByCashier(int $cashierId): Collection
    {
        return $this->model->where('cashier_id', $cashierId)
            ->with(['transactions'])
            ->orderBy('opened_at', 'desc')
            ->get();
    }

    public function getSessionsByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('opened_at', [$startDate, $endDate])
            ->with(['cashier', 'transactions'])
            ->orderBy('opened_at', 'desc')
            ->get();
    }

    public function getCurrentOpenSession(int $cashierId, ?int $terminalId = null): ?POSSession
    {
        $query = $this->model->where('cashier_id', $cashierId)
            ->where('status', 'open');

        if ($terminalId) {
            $query->where('terminal_id', $terminalId);
        }

        return $query->first();
    }
}
