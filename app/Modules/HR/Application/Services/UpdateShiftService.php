<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdateShiftServiceInterface;
use Modules\HR\Application\DTOs\ShiftData;
use Modules\HR\Domain\Entities\Shift;
use Modules\HR\Domain\Exceptions\ShiftNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;
use Modules\HR\Domain\ValueObjects\ShiftType;

class UpdateShiftService extends BaseService implements UpdateShiftServiceInterface
{
    public function __construct(
        private readonly ShiftRepositoryInterface $shiftRepository,
    ) {
        parent::__construct($this->shiftRepository);
    }

    protected function handle(array $data): Shift
    {
        $id = (int) ($data['id'] ?? 0);
        $shift = $this->shiftRepository->find($id);

        if ($shift === null) {
            throw new ShiftNotFoundException($id);
        }

        $dto = ShiftData::fromArray($data);

        if ($shift->getTenantId() !== $dto->tenantId) {
            throw new ShiftNotFoundException($id);
        }

        $updated = new Shift(
            tenantId: $shift->getTenantId(),
            name: $dto->name,
            code: $dto->code,
            shiftType: ShiftType::from($dto->shiftType),
            startTime: $dto->startTime,
            endTime: $dto->endTime,
            breakDuration: $dto->breakDuration,
            workDays: $dto->workDays,
            graceMinutes: $dto->graceMinutes,
            overtimeThreshold: $dto->overtimeThreshold,
            isNightShift: $dto->isNightShift,
            metadata: $dto->metadata,
            isActive: $dto->isActive,
            createdAt: $shift->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $shift->getId(),
        );

        return $this->shiftRepository->save($updated);
    }
}
