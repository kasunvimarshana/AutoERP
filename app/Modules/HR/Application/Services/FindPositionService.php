<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindPositionServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class FindPositionService extends BaseService implements FindPositionServiceInterface
{
    public function __construct(private readonly PositionRepositoryInterface $positionRepository)
    {
        parent::__construct($positionRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Position>
     */
    public function getByDepartment(int $departmentId): array
    {
        return $this->positionRepository->getByDepartment($departmentId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
