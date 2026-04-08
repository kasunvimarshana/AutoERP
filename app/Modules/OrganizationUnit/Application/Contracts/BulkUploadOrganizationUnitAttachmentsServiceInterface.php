<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for bulk-uploading multiple attachments for an organization unit in one operation.
 *
 * @method Collection execute(array $data = [])
 */
interface BulkUploadOrganizationUnitAttachmentsServiceInterface
{
    /**
     * Upload multiple attachments and return the persisted collection.
     *
     * Expected $data keys:
     *   - organization_unit_id (int)
     *   - files                (UploadedFile[])
     *   - type                 (string|null)
     *   - metadata             (array|null)
     *
     * @return Collection<int, \Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment>
     */
    public function execute(array $data): Collection;
}
