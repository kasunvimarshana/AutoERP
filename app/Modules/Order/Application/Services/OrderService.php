<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\Application\Contracts\OrderServiceInterface;
use Modules\Order\Application\DTOs\OrderData;
use Modules\Order\Domain\Events\OrderCreated;
use Modules\Order\Domain\Exceptions\OrderNotFoundException;
use Modules\Order\Domain\RepositoryInterfaces\OrderRepositoryInterface;

final class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
    ) {}

    public function create(OrderData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload                   = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id']      = $tenantId;
            $payload['uuid']           = (string) Str::uuid();
            $payload['reference_number'] = $payload['reference_number']
                ?? $this->generateReferenceNumber($dto->type, $tenantId);

            $lines = $payload['lines'] ?? [];
            unset($payload['lines']);

            $payload = $this->computeOrderTotals($payload, $lines);

            $order = $this->repository->create($payload);

            foreach ($lines as $index => $line) {
                $line          = $this->computeLineTotals($line);
                $line['uuid']  = (string) Str::uuid();
                $line['order_id']    = $order->id;
                $line['line_number'] = $index + 1;
                $order->lines()->create($line);
            }

            $order->refresh();

            OrderCreated::dispatch($order, $tenantId);

            return $order;
        });
    }

    public function update(int $id, OrderData $dto): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new OrderNotFoundException($id);
        }

        return DB::transaction(function () use ($id, $dto) {
            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $lines   = $payload['lines'] ?? [];
            unset($payload['lines']);

            if (! empty($lines)) {
                $payload = $this->computeOrderTotals($payload, $lines);
            }

            $order = $this->repository->update($id, $payload);

            if (! empty($lines)) {
                $order->lines()->delete();
                foreach ($lines as $index => $line) {
                    $line               = $this->computeLineTotals($line);
                    $line['uuid']       = (string) Str::uuid();
                    $line['order_id']   = $id;
                    $line['line_number'] = $index + 1;
                    $order->lines()->create($line);
                }
                $order->refresh();
            }

            return $order;
        });
    }

    public function updateStatus(int $id, string $status, int $tenantId): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new OrderNotFoundException($id);
        }

        $updates = ['status' => $status];

        if ($status === 'confirmed') {
            $updates['confirmed_at'] = now();
        } elseif ($status === 'completed') {
            $updates['completed_at'] = now();
        }

        return $this->repository->update($id, $updates);
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new OrderNotFoundException($id);
        }

        return $this->repository->delete($id);
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->findWithLines($id);

        if (! $record) {
            throw new OrderNotFoundException($id);
        }

        return $record;
    }

    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? (int) config('core.pagination.per_page', 15);
        $repo    = clone $this->repository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }

    private function computeLineTotals(array $line): array
    {
        $quantity        = (float) ($line['quantity'] ?? 0);
        $unitPrice       = (float) ($line['unit_price'] ?? 0);
        $discountPercent = (float) ($line['discount_percent'] ?? 0);
        $taxRate         = (float) ($line['tax_rate'] ?? 0);

        $subtotal              = $quantity * $unitPrice * (1 - $discountPercent / 100);
        $discountAmount        = $quantity * $unitPrice * ($discountPercent / 100);
        $taxAmount             = $subtotal * ($taxRate / 100);
        $total                 = $subtotal + $taxAmount;

        $line['discount_amount'] = round($discountAmount, 6);
        $line['subtotal']        = round($subtotal, 6);
        $line['tax_amount']      = round($taxAmount, 6);
        $line['total']           = round($total, 6);

        return $line;
    }

    private function computeOrderTotals(array $payload, array $lines): array
    {
        $subtotal = 0.0;
        $taxAmount = 0.0;

        foreach ($lines as $line) {
            $computed   = $this->computeLineTotals($line);
            $subtotal  += (float) ($computed['subtotal'] ?? 0);
            $taxAmount += (float) ($computed['tax_amount'] ?? 0);
        }

        $discount        = (float) ($payload['discount_amount'] ?? 0);
        $shipping        = (float) ($payload['shipping_amount'] ?? 0);
        $total           = $subtotal + $taxAmount - $discount + $shipping;

        $payload['subtotal']     = round($subtotal, 6);
        $payload['tax_amount']   = round($taxAmount, 6);
        $payload['total_amount'] = round($total, 6);

        return $payload;
    }

    private function generateReferenceNumber(string $type, int $tenantId): string
    {
        $prefix = strtoupper($type === 'purchase' ? 'PO' : 'SO');

        return sprintf('%s-%d-%s', $prefix, $tenantId, strtoupper(Str::random(8)));
    }
}
