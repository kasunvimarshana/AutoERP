<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Training;

interface TrainingRepositoryInterface extends RepositoryInterface
{
    public function save(Training $training): Training;

    /**
     * Return all trainings with a given status.
     *
     * @return array<int, Training>
     */
    public function getByStatus(string $status): array;
}
