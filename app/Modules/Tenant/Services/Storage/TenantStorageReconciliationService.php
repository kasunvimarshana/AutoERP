<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Tenant\Models\TenantDocumentModel;
use RuntimeException;
use Throwable;

final class TenantStorageReconciliationService
{
    public function __construct(
        private readonly TenantDocumentModel $documents,
        private readonly FileStorageServiceInterface $files,
        private readonly TenantStoragePathPolicy $paths,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Compare authoritative document metadata with the tenant's private object prefix.
     * This is an on-demand health operation, not part of the upload transaction.
     *
     * @return array{
     *   measured_at:string,
     *   healthy:bool,
     *   storage_reachable:bool,
     *   error_code:?string,
     *   error_message:?string,
     *   tracked_files:int,
     *   actual_files:int,
     *   tracked_bytes:int,
     *   actual_bytes:int,
     *   missing_files:int,
     *   orphan_files:int,
     *   size_mismatches:int,
     *   unreadable_files:int,
     *   invalid_metadata_paths:int
     * }
     */
    public function reconcile(int $tenantId): array
    {
        $disk = $this->disk();
        $tracked = [];
        $invalidMetadataPaths = 0;

        $rows = $this->documents->newQuery()
            ->where('tenant_id', $tenantId)
            ->get(['storage_path', 'size_bytes']);

        foreach ($rows as $document) {
            try {
                $path = $this->paths->canonicalize(
                    $tenantId,
                    (string) $document->getAttribute('storage_path'),
                );
                $tracked[$path] = (int) $document->getAttribute('size_bytes');
            } catch (Throwable) {
                $invalidMetadataPaths++;
            }
        }

        $actual = [];
        $unreadableFiles = 0;
        try {
            $storedFiles = $this->files->allFiles($this->paths->documentDirectory($tenantId), $disk);
        } catch (Throwable) {
            return [
                'measured_at' => $this->clock->now()->format(DATE_ATOM),
                'healthy' => false,
                'storage_reachable' => false,
                'error_code' => 'STORAGE_RECONCILIATION_UNAVAILABLE',
                'error_message' => 'Private tenant storage could not be inspected.',
                'tracked_files' => count($tracked),
                'actual_files' => 0,
                'tracked_bytes' => array_sum($tracked),
                'actual_bytes' => 0,
                'missing_files' => 0,
                'orphan_files' => 0,
                'size_mismatches' => 0,
                'unreadable_files' => 0,
                'invalid_metadata_paths' => $invalidMetadataPaths,
            ];
        }

        foreach ($storedFiles as $candidate) {
            try {
                $path = $this->paths->canonicalize($tenantId, $candidate);
                $actual[$path] = $this->files->size($path, $disk);
            } catch (Throwable) {
                $unreadableFiles++;
            }
        }

        $missingFiles = count(array_diff_key($tracked, $actual));
        $orphanFiles = count(array_diff_key($actual, $tracked));
        $sizeMismatches = 0;
        foreach (array_intersect_key($tracked, $actual) as $path => $size) {
            if ($actual[$path] !== $size) {
                $sizeMismatches++;
            }
        }

        $healthy = $missingFiles === 0
            && $orphanFiles === 0
            && $sizeMismatches === 0
            && $unreadableFiles === 0
            && $invalidMetadataPaths === 0;

        return [
            'measured_at' => $this->clock->now()->format(DATE_ATOM),
            'healthy' => $healthy,
            'storage_reachable' => true,
            'error_code' => null,
            'error_message' => null,
            'tracked_files' => count($tracked),
            'actual_files' => count($actual),
            'tracked_bytes' => array_sum($tracked),
            'actual_bytes' => array_sum($actual),
            'missing_files' => $missingFiles,
            'orphan_files' => $orphanFiles,
            'size_mismatches' => $sizeMismatches,
            'unreadable_files' => $unreadableFiles,
            'invalid_metadata_paths' => $invalidMetadataPaths,
        ];
    }

    private function disk(): string
    {
        $disk = trim((string) config('tenant.documents.disk', 'tenant_private'));
        if ($disk === '') {
            throw new RuntimeException('The tenant private storage disk is not configured.');
        }

        return $disk;
    }
}
