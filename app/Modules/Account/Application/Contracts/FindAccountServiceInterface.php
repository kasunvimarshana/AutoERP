<?php

declare(strict_types=1);

namespace Modules\Account\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying accounts.
 *
 * Separates read operations from write (create/update/delete) concerns,
 * adhering to the Interface Segregation and Single Responsibility principles.
 */
interface FindAccountServiceInterface extends ReadServiceInterface {}
