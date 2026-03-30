<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * Contract for updating the mutable classification fields of an existing
 * organization-unit attachment (type and/or metadata) without replacing the
 * underlying stored file.
 *
 * @method \Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment execute(array $data = [])
 */
interface UpdateOrganizationUnitAttachmentServiceInterface extends WriteServiceInterface {}
