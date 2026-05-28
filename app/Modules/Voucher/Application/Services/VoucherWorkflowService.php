<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Services;

use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherWorkflowServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherStatusHistoryRepositoryInterface;
use Modules\Voucher\Domain\Constants\VoucherStatus;

final class VoucherWorkflowService implements VoucherWorkflowServiceInterface
{
    public function __construct(
        private readonly VoucherRepositoryInterface $voucherRepository,
        private readonly VoucherStatusHistoryRepositoryInterface $statusHistoryRepository,
    ) {
    }

    public function submit(int $voucherId, array $payload = []): Result
    {
        return $this->transition($voucherId, VoucherStatus::SUBMITTED, 'submit', $payload);
    }

    public function approve(int $voucherId, array $payload = []): Result
    {
        return $this->transition($voucherId, VoucherStatus::APPROVED, 'approve', $payload);
    }

    public function reject(int $voucherId, array $payload = []): Result
    {
        return $this->transition($voucherId, VoucherStatus::REJECTED, 'reject', $payload);
    }

    public function post(int $voucherId, array $payload = []): Result
    {
        return $this->transition($voucherId, VoucherStatus::POSTED, 'post', $payload);
    }

    public function cancel(int $voucherId, array $payload = []): Result
    {
        return $this->transition($voucherId, VoucherStatus::CANCELLED, 'cancel', $payload);
    }

    public function reverse(int $voucherId, array $payload = []): Result
    {
        return $this->transition($voucherId, VoucherStatus::REVERSED, 'reverse', $payload);
    }

    public function history(int $voucherId): Result
    {
        return Result::success($this->statusHistoryRepository->list(['voucher_id' => $voucherId]));
    }

    private function transition(int $voucherId, string $toStatus, string $transition, array $payload): Result
    {
        $voucher = $this->voucherRepository->update($voucherId, ['status' => $toStatus]);
        $this->statusHistoryRepository->create([
            'voucher_id' => $voucherId,
            'to_status' => $toStatus,
            'transition' => $transition,
            'comments' => $payload['comments'] ?? null,
            'changed_by' => $payload['acted_by'] ?? null,
            'changed_at' => now(),
        ]);

        return Result::success($voucher);
    }
}
