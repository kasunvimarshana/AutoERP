<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\CreatePayrollItemServiceInterface;
use Modules\HR\Application\DTOs\PayrollItemData;
use Modules\HR\Domain\Entities\PayrollItem;
use Modules\HR\Domain\RepositoryInterfaces\PayrollItemRepositoryInterface;

class CreatePayrollItemService extends BaseService implements CreatePayrollItemServiceInterface
{
    public function __construct(
        private readonly PayrollItemRepositoryInterface $itemRepository,
    ) {
        parent::__construct($this->itemRepository);
    }

    protected function handle(array $data): PayrollItem
    {
        $dto = PayrollItemData::fromArray($data);

        $existing = $this->itemRepository->findByTenantAndCode($dto->tenantId, $dto->code);
        if ($existing !== null) {
            throw new DomainException("Payroll item code '{$dto->code}' already exists for this tenant.");
        }

        $now = new \DateTimeImmutable;
        $item = new PayrollItem(
            tenantId: $dto->tenantId,
            name: $dto->name,
            code: $dto->code,
            type: $dto->type,
            calculationType: $dto->calculationType,
            value: $dto->value,
            isActive: $dto->isActive,
            isTaxable: $dto->isTaxable,
            accountId: $dto->accountId,
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->itemRepository->save($item);
    }
}
