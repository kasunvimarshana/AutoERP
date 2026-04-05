<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

use Illuminate\Http\UploadedFile;
use Modules\User\Domain\Entities\User;

interface UploadAvatarServiceInterface
{
    public function execute(int $id, UploadedFile $file): User;
}
