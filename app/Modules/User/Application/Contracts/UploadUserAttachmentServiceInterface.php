<?php

namespace Modules\User\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * @method \Modules\User\Domain\Entities\UserAttachment execute(array $data = [])
 */
interface UploadUserAttachmentServiceInterface extends WriteServiceInterface
{
}
