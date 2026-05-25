<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Specifications;

/**
 * @template T of object
 */
interface SpecificationInterface
{
    /**
     * @param T $candidate
     */
    public function isSatisfiedBy(object $candidate): bool;
}
