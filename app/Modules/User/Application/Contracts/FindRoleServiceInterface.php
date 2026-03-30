<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying roles.
 *
 * Separates read operations from write (create/update/delete) concerns,
 * adhering to the Interface Segregation and Single Responsibility principles.
 */
interface FindRoleServiceInterface extends ReadServiceInterface {}
