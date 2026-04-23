<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteShiftServiceInterface;
use Modules\HR\Domain\Exceptions\ShiftNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;

class DeleteShiftService extends BaseService implements DeleteShiftServiceInterface
{
    public function __construct(
        private readonly ShiftRepositoryInterface $shiftRepository,
    ) {
        parent::__construct($this->shiftRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $shift = $this->shiftRepository->find($id);

        if ($shift === null) {
            throw new ShiftNotFoundException($id);
        }

        return $this->shiftRepository->delete($id);
    }
}
