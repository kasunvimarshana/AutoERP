<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\CreateShiftServiceInterface;
use Modules\HR\Application\DTOs\ShiftData;
use Modules\HR\Domain\Entities\Shift;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;
use Modules\HR\Domain\ValueObjects\ShiftType;

class CreateShiftService extends BaseService implements CreateShiftServiceInterface
{
    public function __construct(
        private readonly ShiftRepositoryInterface $shiftRepository,
    ) {
        parent::__construct($this->shiftRepository);
    }

    protected function handle(array $data): Shift
    {
        $dto = ShiftData::fromArray($data);

        $existing = $this->shiftRepository->findByTenantAndCode($dto->tenantId, $dto->code);
        if ($existing !== null) {
            throw new DomainException("Shift code '{$dto->code}' already exists for this tenant.");
        }

        $now = new \DateTimeImmutable;
        $shift = new Shift(
            tenantId: $dto->tenantId,
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
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->shiftRepository->save($shift);
    }
}
