<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\POS\Domain\Entities\PosTransaction;
use Modules\POS\Domain\Entities\PosTransactionLine;
use Modules\POS\Domain\RepositoryInterfaces\PosTransactionRepositoryInterface;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosTransactionLineModel;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosTransactionModel;

class EloquentPosTransactionRepository implements PosTransactionRepositoryInterface
{
    public function __construct(
        private readonly PosTransactionModel $model,
        private readonly PosTransactionLineModel $lineModel,
    ) {}

    public function findById(int $id): ?PosTransaction
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) { return null; }
        $lines = $this->lineModel->newQuery()
            ->where('pos_transaction_id', $id)
            ->get()
            ->map(fn($l) => $this->toLineEntity($l))
            ->all();
        return $this->toEntity($m, $lines);
    }

    public function findBySession(int $sessionId): array
    {
        return $this->model->newQuery()
            ->where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($m) {
                $lines = $this->lineModel->newQuery()
                    ->where('pos_transaction_id', $m->id)
                    ->get()
                    ->map(fn($l) => $this->toLineEntity($l))
                    ->all();
                return $this->toEntity($m, $lines);
            })
            ->all();
    }

    public function create(array $data, array $lines): PosTransaction
    {
        $m = $this->model->newQuery()->create($data);
        foreach ($lines as $line) {
            $this->lineModel->newQuery()->create(
                array_merge($line, ['pos_transaction_id' => $m->id])
            );
        }
        return $this->findById($m->id);
    }

    public function updateStatus(int $id, string $status): ?PosTransaction
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) { return null; }
        $m->update(['status' => $status]);
        return $this->findById($id);
    }

    private function toEntity(PosTransactionModel $m, array $lines): PosTransaction
    {
        return new PosTransaction(
            $m->id, $m->tenant_id, $m->session_id, $m->customer_id,
            $m->type, $m->status, $m->currency,
            (float) $m->subtotal, (float) $m->tax_total,
            (float) $m->discount_total, (float) $m->total,
            $m->payment_method,
            $m->amount_tendered !== null ? (float) $m->amount_tendered : null,
            $m->change_given !== null ? (float) $m->change_given : null,
            $m->reference, $m->notes, $lines,
            $m->created_at, $m->updated_at,
        );
    }

    private function toLineEntity(PosTransactionLineModel $l): PosTransactionLine
    {
        return new PosTransactionLine(
            $l->id, $l->pos_transaction_id,
            $l->product_id, $l->variant_id,
            $l->product_name, $l->sku,
            (float) $l->quantity, (float) $l->unit_price,
            (float) $l->discount_amount, (float) $l->tax_amount,
            (float) $l->line_total, $l->created_at,
        );
    }
}
