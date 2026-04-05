<?php
declare(strict_types=1);
namespace Modules\Attachment\Application\Services;
use Illuminate\Http\UploadedFile;
use Modules\Attachment\Application\Contracts\UploadAttachmentServiceInterface;
use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;
class UploadAttachmentService implements UploadAttachmentServiceInterface {
    public function __construct(private readonly AttachmentRepositoryInterface $repo) {}
    public function execute(UploadedFile $file, int $tenantId, string $type, int $id, ?int $uploadedBy, ?string $category, string $disk = 'local'): Attachment {
        $filename = uniqid('att_',true).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("attachments/{$tenantId}/{$type}/{$id}", $filename, $disk);
        return $this->repo->create([
            'tenant_id'=>$tenantId,'attachable_type'=>$type,'attachable_id'=>$id,
            'filename'=>$filename,'original_name'=>$file->getClientOriginalName(),
            'mime_type'=>$file->getMimeType(),'size'=>$file->getSize(),
            'path'=>$path,'disk'=>$disk,'category'=>$category,'uploaded_by'=>$uploadedBy,
        ]);
    }
}
