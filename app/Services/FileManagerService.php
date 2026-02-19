<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagerService
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MediaFile::where('tenant_id', $tenantId)
            ->with(['category', 'uploadedBy']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['attachable_type'])) {
            $query->where('attachable_type', $filters['attachable_type']);
        }
        if (isset($filters['mime_type'])) {
            $query->where('mime_type', $filters['mime_type']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function upload(string $tenantId, UploadedFile $file, array $data = []): MediaFile
    {
        return DB::transaction(function () use ($tenantId, $file, $data) {
            $disk = $data['disk'] ?? 'local';
            $directory = "{$tenantId}/files";
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid().($extension ? '.'.$extension : '');

            $path = $file->storeAs($directory, $filename, $disk);

            return MediaFile::create(array_merge($data, [
                'tenant_id' => $tenantId,
                'disk' => $disk,
                'path' => $path,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'checksum' => md5_file($file->getRealPath()),
            ]));
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $file = MediaFile::findOrFail($id);
            try {
                Storage::disk($file->disk->value)->delete($file->path);
            } catch (\Throwable) {
                // Proceed with soft delete even if storage deletion fails
            }
            $file->delete();
        });
    }
}
