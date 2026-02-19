<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Factory Interface
 *
 * Base contract for all factory classes that create domain objects.
 * Factories encapsulate complex object creation logic.
 */
interface FactoryInterface
{
    /**
     * Create an instance from data.
     *
     * @param  array  $data  Data to create the instance from
     * @return mixed Created instance
     */
    public function make(array $data): mixed;

    /**
     * Create and persist an instance.
     *
     * @param  array  $data  Data to create the instance from
     * @return mixed Created and persisted instance
     */
    public function create(array $data): mixed;
}
