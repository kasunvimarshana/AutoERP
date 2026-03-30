<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying brands.
 *
 * Separates read operations from write (create/update/delete) concerns,
 * adhering to the Interface Segregation and Single Responsibility principles.
 */
interface FindBrandServiceInterface extends ReadServiceInterface {}
