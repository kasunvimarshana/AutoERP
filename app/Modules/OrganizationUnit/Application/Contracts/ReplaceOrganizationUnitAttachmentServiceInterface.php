<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * Contract for replacing the file of an existing organization-unit attachment.
 *
 * The replace operation deletes the old stored file, persists the new
 * UploadedFile via the storage strategy, and updates the attachment record
 * in the repository — all within a single transaction.
 *
 * @method \Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment execute(array $data = [])
 */
interface ReplaceOrganizationUnitAttachmentServiceInterface extends WriteServiceInterface {}
