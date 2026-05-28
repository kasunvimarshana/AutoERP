<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherManagementServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherAllocationRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherLineRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;

final class VoucherManagementService implements VoucherManagementServiceInterface
{
    public function __construct(
        private readonly VoucherRepositoryInterface $voucherRepository,
        private readonly VoucherLineRepositoryInterface $voucherLineRepository,
        private readonly VoucherAllocationRepositoryInterface $voucherAllocationRepository,
    ) {
    }

    public function list(array $criteria = []): Result { return Result::success($this->voucherRepository->list($criteria)); }
    public function create(array $payload): Result { return Result::success($this->voucherRepository->create($payload)); }

    public function getById(int $id): Result
    {
        $voucher = $this->voucherRepository->findById($id);
        if ($voucher === null) {
            return Result::failure(new Error('VOUCHER_NOT_FOUND', 'Voucher not found.', ['id' => $id]));
        }

        return Result::success($voucher);
    }

    public function update(int $id, array $payload): Result
    {
        if ($this->voucherRepository->findById($id) === null) {
            return Result::failure(new Error('VOUCHER_NOT_FOUND', 'Voucher not found.', ['id' => $id]));
        }

        return Result::success($this->voucherRepository->update($id, $payload));
    }

    public function delete(int $id): Result
    {
        if ($this->voucherRepository->findById($id) === null) {
            return Result::failure(new Error('VOUCHER_NOT_FOUND', 'Voucher not found.', ['id' => $id]));
        }

        return Result::success(['deleted' => $this->voucherRepository->delete($id)]);
    }

    public function upsertLines(int $voucherId, array $lines): Result { return Result::success($lines); }
    public function listAllocations(int $voucherId): Result { return Result::success($this->voucherAllocationRepository->list(['voucher_id' => $voucherId])); }
    public function addAllocation(int $voucherId, array $payload): Result { $payload['voucher_id'] = $voucherId; return Result::success($this->voucherAllocationRepository->create($payload)); }
    public function updateAllocation(int $allocationId, array $payload): Result { return Result::success($this->voucherAllocationRepository->update($allocationId, $payload)); }
}