<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class PerformanceReviewNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct('PerformanceReview', $id);
    }
}
