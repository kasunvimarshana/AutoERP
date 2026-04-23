<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\HR\Application\Contracts\UpdatePayrollItemServiceInterface;
use Modules\HR\Application\DTOs\PayrollItemData;
use Modules\HR\Domain\Entities\PayrollItem;
use Modules\HR\Domain\RepositoryInterfaces\PayrollItemRepositoryInterface;

class UpdatePayrollItemService extends BaseService implements UpdatePayrollItemServiceInterface
{
    public function __construct(
        private readonly PayrollItemRepositoryInterface $itemRepository,
    ) {
        parent::__construct($this->itemRepository);
    }

    protected function handle(array $data): PayrollItem
    {
        $id = (int) ($data['id'] ?? 0);
        $item = $this->itemRepository->find($id);

        if ($item === null) {
            throw new NotFoundException('PayrollItem', $id);
        }

        $dto = PayrollItemData::fromArray($data);

        if ($item->getTenantId() !== $dto->tenantId) {
            throw new NotFoundException('PayrollItem', $id);
        }

        $updated = new PayrollItem(
            tenantId: $item->getTenantId(),
            name: $dto->name,
            code: $dto->code,
            type: $dto->type,
            calculationType: $dto->calculationType,
            value: $dto->value,
            isActive: $dto->isActive,
            isTaxable: $dto->isTaxable,
            accountId: $dto->accountId,
            metadata: $dto->metadata,
            createdAt: $item->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $item->getId(),
        );

        return $this->itemRepository->save($updated);
    }
}
