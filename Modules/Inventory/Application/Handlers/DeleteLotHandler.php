<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Inventory\Domain\Contracts\LotRepositoryInterface;

class DeleteLotHandler extends BaseHandler
{
    public function __construct(
        private readonly LotRepositoryInterface $lotRepository,
    ) {}

    public function handle(int $tenantId, int $id): void
    {
        $lot = $this->lotRepository->findById($tenantId, $id);

        if ($lot === null) {
            throw new \DomainException("Inventory lot [{$id}] not found.");
        }

        $this->lotRepository->delete($tenantId, $id);
    }
}
