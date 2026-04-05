<?php
declare(strict_types=1);
namespace Modules\Attachment\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attachment\Application\Contracts\UploadAttachmentServiceInterface;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;
use Illuminate\Routing\Controller;
class AttachmentController extends Controller {
    public function __construct(
        private readonly AttachmentRepositoryInterface $repo,
        private readonly UploadAttachmentServiceInterface $uploadService,
    ) {}
    public function index(Request $r): JsonResponse { return response()->json($this->repo->findByAttachable($r->input('type',''),$r->input('id',0))); }
    public function store(Request $r): JsonResponse {
        $att=$this->uploadService->execute($r->file('file'),(int)$r->input('tenant_id'),$r->input('type',''),(int)$r->input('id'),
            $r->input('uploaded_by')??(int)null,$r->input('category'),$r->input('disk','local'));
        return response()->json($att,201);
    }
    public function destroy(int $id): JsonResponse { $this->repo->delete($id); return response()->json(null,204); }
}
