<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Returns\Application\Contracts\ReturnServiceInterface;
use Modules\Returns\Application\DTOs\ReturnData;
use Modules\Returns\Domain\Events\ReturnCreated;
use Modules\Returns\Domain\Exceptions\ReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRepositoryInterface;

final class ReturnService implements ReturnServiceInterface
{
    public function __construct(
        private readonly ReturnRepositoryInterface $repository,
    ) {}

    public function create(ReturnData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $payload                     = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['tenant_id']        = $tenantId;
            $payload['uuid']             = (string) Str::uuid();
            $payload['reference_number'] = $payload['reference_number']
                ?? $this->generateReferenceNumber($dto->type, $tenantId);

            $lines = $payload['lines'] ?? [];
            unset($payload['lines']);

            $payload = $this->computeReturnTotals($payload, $lines);

            $return = $this->repository->create($payload);

            foreach ($lines as $line) {
                $line           = $this->computeLineSubtotal($line);
                $line['uuid']   = (string) Str::uuid();
                $line['return_id'] = $return->id;
                $return->lines()->create($line);
            }

            $return->refresh();

            ReturnCreated::dispatch($return, $tenantId);

            return $return;
        });
    }

    public function update(int $id, ReturnData $dto): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new ReturnNotFoundException($id);
        }

        return DB::transaction(function () use ($id, $dto) {
            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $lines   = $payload['lines'] ?? [];
            unset($payload['lines']);

            if (! empty($lines)) {
                $payload = $this->computeReturnTotals($payload, $lines);
            }

            $return = $this->repository->update($id, $payload);

            if (! empty($lines)) {
                $return->lines()->delete();
                foreach ($lines as $line) {
                    $line              = $this->computeLineSubtotal($line);
                    $line['uuid']      = (string) Str::uuid();
                    $line['return_id'] = $id;
                    $return->lines()->create($line);
                }
                $return->refresh();
            }

            return $return;
        });
    }

    public function approve(int $id, int $userId, int $tenantId): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new ReturnNotFoundException($id);
        }

        return $this->repository->update($id, [
            'status'      => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function process(int $id, int $userId, int $tenantId): mixed
    {
        $record = $this->repository->findWithLines($id);

        if (! $record) {
            throw new ReturnNotFoundException($id);
        }

        $allReceived = $record->lines->every(
            static fn ($line) => (float) $line->quantity_received >= (float) $line->quantity_requested
        );

        $newStatus = $allReceived ? 'completed' : 'processing';

        return $this->repository->update($id, [
            'status'       => $newStatus,
            'processed_by' => $userId,
            'processed_at' => now(),
        ]);
    }

    public function reject(int $id, string $reason, int $tenantId): mixed
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new ReturnNotFoundException($id);
        }

        return $this->repository->update($id, [
            'status'         => 'rejected',
            'internal_notes' => $reason,
        ]);
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->find($id);

        if (! $record) {
            throw new ReturnNotFoundException($id);
        }

        return $this->repository->delete($id);
    }

    public function find(mixed $id): mixed
    {
        $record = $this->repository->findWithLines($id);

        if (! $record) {
            throw new ReturnNotFoundException($id);
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

    private function computeLineSubtotal(array $line): array
    {
        $quantityRequested = (float) ($line['quantity_requested'] ?? 0);
        $unitPrice         = (float) ($line['unit_price'] ?? 0);

        $line['subtotal'] = round($quantityRequested * $unitPrice, 6);

        return $line;
    }

    private function computeReturnTotals(array $payload, array $lines): array
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $computed  = $this->computeLineSubtotal($line);
            $subtotal += (float) ($computed['subtotal'] ?? 0);
        }

        $feeAmount   = (float) ($payload['fee_amount'] ?? 0);
        $totalAmount = $subtotal - $feeAmount;

        $payload['subtotal']     = round($subtotal, 6);
        $payload['tax_amount']   = 0.0;
        $payload['total_amount'] = round($totalAmount, 6);

        return $payload;
    }

    private function generateReferenceNumber(string $type, int $tenantId): string
    {
        $prefix = $type === 'purchase_return' ? 'PR' : 'SR';

        return sprintf('%s-%d-%s', $prefix, $tenantId, strtoupper(Str::random(8)));
    }
}
