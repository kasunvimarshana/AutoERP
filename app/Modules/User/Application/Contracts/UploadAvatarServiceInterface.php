<?php
namespace Modules\User\Application\Contracts;

use Illuminate\Http\UploadedFile;
use Modules\User\Domain\Entities\User;

interface UploadAvatarServiceInterface
{
    public function execute(User $user, UploadedFile $file): User;
}
