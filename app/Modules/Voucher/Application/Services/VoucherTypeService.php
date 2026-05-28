<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Services;

use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\Services\VoucherTypeServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherTypeRepositoryInterface;

final class VoucherTypeService implements VoucherTypeServiceInterface
{
    public function __construct(private readonly VoucherTypeRepositoryInterface $voucherTypeRepository) {}
    public function list(array $criteria = []): Result { return Result::success($this->voucherTypeRepository->list($criteria)); }
    public function create(array $payload): Result { return Result::success($this->voucherTypeRepository->create($payload)); }
    public function update(int $id, array $payload): Result { return Result::success($this->voucherTypeRepository->update($id, $payload)); }
    public function setActive(int $id, bool $isActive): Result { return Result::success($this->voucherTypeRepository->update($id, ['is_active' => $isActive])); }
}