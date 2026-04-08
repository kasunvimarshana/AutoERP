<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\OrganizationUnit\Domain\Entities\OrganizationUnit execute(array $data = [])
 */
interface UpdateOrganizationUnitServiceInterface extends WriteServiceInterface {}
