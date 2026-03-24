<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment execute(array $data = [])
 */
interface UploadOrganizationUnitAttachmentServiceInterface extends WriteServiceInterface {}
