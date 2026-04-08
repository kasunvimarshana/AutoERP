<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class GetPosition
{
    public function __construct(private readonly PositionRepositoryInterface $repo) {}

    public function execute(int $id): ?Position
    {
        return $this->repo->find($id);
    }
}
