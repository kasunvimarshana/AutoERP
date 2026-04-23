<?php

declare(strict_types=1);

namespace Modules\HR\Domain\ValueObjects;

enum PerformanceRating: string
{
    case OUTSTANDING = 'outstanding';
    case EXCEEDS_EXPECTATIONS = 'exceeds_expectations';
    case MEETS_EXPECTATIONS = 'meets_expectations';
    case NEEDS_IMPROVEMENT = 'needs_improvement';
    case UNSATISFACTORY = 'unsatisfactory';
}
