<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Services;

use Modules\Pos\Application\Commands\CancelPosOrderCommand;
use Modules\Pos\Application\Commands\CreatePosOrderCommand;
use Modules\Pos\Application\Commands\DeletePosOrderCommand;
use Modules\Pos\Application\Commands\PayPosOrderCommand;
use Modules\Pos\Application\Commands\RefundPosOrderCommand;
use Modules\Pos\Application\Handlers\CancelPosOrderHandler;
use Modules\Pos\Application\Handlers\CreatePosOrderHandler;
use Modules\Pos\Application\Handlers\DeletePosOrderHandler;
use Modules\Pos\Application\Handlers\PayPosOrderHandler;
use Modules\Pos\Application\Handlers\RefundPosOrderHandler;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Entities\PosOrder;

class PosOrderService
{
    public function __construct(
        private readonly PosOrderRepositoryInterface $repository,
        private readonly CreatePosOrderHandler $createHandler,
        private readonly PayPosOrderHandler $payHandler,
        private readonly CancelPosOrderHandler $cancelHandler,
        private readonly RefundPosOrderHandler $refundHandler,
        private readonly DeletePosOrderHandler $deleteHandler,
    ) {}

    public function createOrder(CreatePosOrderCommand $cmd): PosOrder
    {
        return $this->createHandler->handle($cmd);
    }

    public function payOrder(PayPosOrderCommand $cmd): PosOrder
    {
        return $this->payHandler->handle($cmd);
    }

    public function cancelOrder(CancelPosOrderCommand $cmd): PosOrder
    {
        return $this->cancelHandler->handle($cmd);
    }

    public function refundOrder(RefundPosOrderCommand $cmd): PosOrder
    {
        return $this->refundHandler->handle($cmd);
    }

    public function deleteOrder(DeletePosOrderCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?PosOrder
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }

    public function findBySession(int $sessionId, int $tenantId): array
    {
        return $this->repository->findBySession($sessionId, $tenantId);
    }

    public function findLines(int $orderId, int $tenantId): array
    {
        return $this->repository->findLines($orderId, $tenantId);
    }

    public function findPayments(int $orderId, int $tenantId): array
    {
        return $this->repository->findPayments($orderId, $tenantId);
    }
}
