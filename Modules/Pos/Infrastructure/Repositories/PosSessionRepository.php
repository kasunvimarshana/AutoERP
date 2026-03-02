<?php

declare(strict_types=1);

namespace Modules\Pos\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Domain\Entities\PosSession;
use Modules\Pos\Domain\Enums\PosSessionStatus;
use Modules\Pos\Infrastructure\Models\PosSessionModel;

class PosSessionRepository extends BaseRepository implements PosSessionRepositoryInterface
{
    protected function model(): string
    {
        return PosSessionModel::class;
    }

    public function save(PosSession $session): PosSession
    {
        if ($session->id !== null) {
            /** @var PosSessionModel $model */
            $model = $this->newQuery()
                ->where('tenant_id', $session->tenantId)
                ->findOrFail($session->id);
        } else {
            $model = new PosSessionModel;
            $model->tenant_id = $session->tenantId;
        }

        $model->user_id = $session->userId;
        $model->reference = $session->reference;
        $model->status = $session->status;
        $model->opened_at = $session->openedAt;
        $model->closed_at = $session->closedAt;
        $model->currency = $session->currency;
        $model->opening_float = $session->openingFloat;
        $model->closing_float = $session->closingFloat;
        $model->total_sales = $session->totalSales;
        $model->total_refunds = $session->totalRefunds;
        $model->notes = $session->notes;
        $model->save();

        return $this->toEntity($model);
    }

    public function findById(int $id, int $tenantId): ?PosSession
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (PosSessionModel $m) => $this->toEntity($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findActiveByUser(int $tenantId, int $userId): ?PosSession
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', PosSessionStatus::Open->value)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toEntity(PosSessionModel $model): PosSession
    {
        return new PosSession(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            reference: $model->reference,
            status: $model->status,
            openedAt: $model->opened_at?->toIso8601String() ?? '',
            closedAt: $model->closed_at?->toIso8601String(),
            currency: $model->currency,
            openingFloat: (string) $model->opening_float,
            closingFloat: (string) $model->closing_float,
            totalSales: (string) $model->total_sales,
            totalRefunds: (string) $model->total_refunds,
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
