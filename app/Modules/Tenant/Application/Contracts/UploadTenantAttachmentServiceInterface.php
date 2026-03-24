<?php

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\Tenant\Domain\Entities\TenantAttachment execute(array $data = [])
 */
interface UploadTenantAttachmentServiceInterface extends WriteServiceInterface
{
}
