<?php
declare(strict_types=1);
namespace Modules\Attachment\Application\Contracts;
use Illuminate\Http\UploadedFile;
use Modules\Attachment\Domain\Entities\Attachment;
interface UploadAttachmentServiceInterface {
    public function execute(UploadedFile $file, int $tenantId, string $type, int $id, ?int $uploadedBy, ?string $category, string $disk): Attachment;
}
