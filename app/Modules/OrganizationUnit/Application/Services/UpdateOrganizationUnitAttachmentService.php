<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\UpdateOrganizationUnitAttachmentData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Exceptions\AttachmentNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;

/**
 * Updates the mutable classification fields of an existing attachment.
 *
 * Only `type` and `metadata` may be changed without replacing the stored file.
 * The service uses UpdateOrganizationUnitAttachmentData::isProvided() to
 * distinguish "field not sent" from "field explicitly set to null", so omitting
 * a field in the payload always preserves the existing value.
 *
 * Expected $data keys:
 *   - attachment_id  (int)
 *   - type           (string|null) — optional; preserves existing value when absent
 *   - metadata       (array|null)  — optional; preserves existing value when absent
 */
class UpdateOrganizationUnitAttachmentService extends BaseService implements UpdateOrganizationUnitAttachmentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitAttachmentRepositoryInterface $attachmentRepository
    ) {
        parent::__construct($attachmentRepository);
    }

    protected function handle(array $data): OrganizationUnitAttachment
    {
        $attachmentId = (int) $data['attachment_id'];

        $existing = $this->attachmentRepository->find($attachmentId);
        if (! $existing) {
            throw new AttachmentNotFoundException($attachmentId);
        }

        $dto  = UpdateOrganizationUnitAttachmentData::fromArray($data);
        $type = $dto->isProvided('type') ? $dto->type : $existing->getType();
        $meta = $dto->isProvided('metadata') ? $dto->metadata : $existing->getMetadata();

        $existing->updateDetails($type, $meta);

        return $this->attachmentRepository->save($existing);
    }
}
