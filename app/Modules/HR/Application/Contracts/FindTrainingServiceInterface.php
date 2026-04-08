<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindTrainingServiceInterface extends ReadServiceInterface
{
    /**
     * @return array<int, \Modules\HR\Domain\Entities\Training>
     */
    public function getByStatus(string $status): array;
}
