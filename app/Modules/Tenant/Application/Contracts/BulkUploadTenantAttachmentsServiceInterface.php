<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for bulk-uploading multiple attachments for a tenant in one operation.
 *
 * @method Collection execute(array $data = [])
 */
interface BulkUploadTenantAttachmentsServiceInterface
{
    /**
     * Upload multiple attachments and return the persisted collection.
     *
     * Expected $data keys:
     *   - tenant_id (int)
     *   - files     (UploadedFile[])
     *   - type      (string|null)
     *   - metadata  (array|null)
     *
     * @return Collection<int, \Modules\Tenant\Domain\Entities\TenantAttachment>
     */
    public function execute(array $data): Collection;
}
