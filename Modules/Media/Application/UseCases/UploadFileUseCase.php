<?php

namespace Modules\Media\Application\UseCases;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Domain\Contracts\MediaRepositoryInterface;

class UploadFileUseCase
{
    public function __construct(private MediaRepositoryInterface $repository) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            /** @var UploadedFile $file */
            $file = $data['file'];
            $disk   = config('media.default_disk', 'local');
            $folder = $data['folder'] ?? 'uploads';
            $path   = $file->store($folder, $disk);

            return $this->repository->create([
                'id'            => (string) Str::uuid(),
                'tenant_id'     => $data['tenant_id'],
                'uploaded_by'   => $data['uploaded_by'],
                'disk'          => $disk,
                'path'          => $path,
                'filename'      => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'size_bytes'    => $file->getSize(),
                'folder'        => $folder,
                'is_public'     => $data['is_public'] ?? false,
                'model_type'    => $data['model_type'] ?? null,
                'model_id'      => $data['model_id'] ?? null,
            ]);
        });
    }
}
