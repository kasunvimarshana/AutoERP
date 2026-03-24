<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method bool execute(array $data = [])
 */
interface DeleteOrganizationUnitServiceInterface extends WriteServiceInterface {}
