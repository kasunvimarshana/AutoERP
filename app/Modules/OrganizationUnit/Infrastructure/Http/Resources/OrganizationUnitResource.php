<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Infrastructure\Http\Resources\UserResource;

class OrganizationUnitResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly FileStorageServiceInterface $storage,
        private readonly ?Collection $attachments = null,
        private readonly ?Collection $users = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $attachments = $this->attachments;
        $avatarAttachment = $attachments?->first(fn ($attachment): bool => $attachment->getType() === 'avatar');
        $attachmentResources = $attachments?->map(
            fn ($attachment): OrganizationUnitAttachmentResource => new OrganizationUnitAttachmentResource(
                resource: $attachment,
                storage: $this->storage,
            )
        );

        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'type_id' => $this->getTypeId(),
            'parent_id' => $this->getParentId(),
            'manager_user_id' => $this->getManagerUserId(),
            'name' => $this->getName(),
            'code' => $this->getCode(),
            'path' => $this->getPath(),
            'depth' => $this->getDepth(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'description' => $this->getDescription(),
            'avatar_url' => $avatarAttachment !== null ? $this->storage->url($avatarAttachment->getFilePath()) : null,
            'attachments' => $this->when(
                $this->attachments !== null,
                fn (): array => $attachmentResources
                    ->map(fn (OrganizationUnitAttachmentResource $resource): array => $resource->toArray($request))
                    ->values()
                    ->all()
            ),
            'users' => $this->when(
                $this->users !== null,
                fn (): AnonymousResourceCollection => UserResource::collection($this->users)
            ),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
