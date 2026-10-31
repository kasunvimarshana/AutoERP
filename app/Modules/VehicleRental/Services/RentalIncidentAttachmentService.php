<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Contracts\TenantPrivateFileServiceInterface;
use Modules\VehicleRental\Constants\IncidentFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\IncidentPermission;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Models\RentalIncident;
use Modules\VehicleRental\Models\RentalIncidentAttachment;
use Modules\VehicleRental\Models\RentalIncidentEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class RentalIncidentAttachmentService
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly AgreementValidation $contextValidation,
        private readonly TenantPrivateFileServiceInterface $files,
    ) {}

    public function attach(AgreementContext $context, int $incidentId, int $expectedVersion, UploadedFile $file): void
    {
        $this->authorization->assertIncident($context, IncidentPermission::Record);
        $this->contextValidation->assertContext($context);
        Validator::make(['file' => $file], [
            'file' => ['required', 'file', 'mimetypes:'.implode(',', IncidentFields::ATTACHMENT_MIME_TYPES), 'max:'.IncidentFields::ATTACHMENT_MAX_KB],
        ])->validate();
        $stored = null;
        try {
            DB::transaction(function () use ($context, $incidentId, $expectedVersion, $file, &$stored): void {
                $incident = RentalIncident::query()->forContext($context->tenantId, $context->organizationUnitId)
                    ->lockForUpdate()->findOrFail($incidentId);
                if ($incident->row_version !== $expectedVersion || $incident->status !== IncidentStatus::Recorded) {
                    throw new ConflictHttpException('This evidence changed or was reviewed. Reload before adding a file.');
                }
                $checksum = hash_file('sha256', $file->getPathname());
                if ($incident->attachments()->where('checksum_sha256', $checksum)->exists()) {
                    throw ValidationException::withMessages(['file' => ['This file is already attached to the incident.']]);
                }
                try {
                    $stored = $this->files->storeFile(
                        $context->tenantId,
                        'vehicle-rental/incidents/'.$incidentId,
                        $file->getPathname(),
                        $file->getClientOriginalName(),
                        IncidentFields::ATTACHMENT_MIME_TYPES,
                        IncidentFields::ATTACHMENT_MAX_KB * 1024,
                    );
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages(['file' => [$exception->getMessage()]]);
                }
                $attachment = new RentalIncidentAttachment;
                $attachment->forceFill($stored + [
                    'tenant_id' => $context->tenantId,
                    'incident_id' => $incidentId,
                    'actor_id' => $context->actorId,
                    'recorded_at' => now(),
                ])->save();
                $incident->forceFill(['row_version' => $incident->row_version + 1])->save();
                $event = new RentalIncidentEvent;
                $event->forceFill([
                    'tenant_id' => $context->tenantId,
                    'incident_id' => $incidentId,
                    'actor_id' => $context->actorId,
                    'action' => IncidentFields::ATTACHMENT_ADDED,
                    'reason' => $attachment->original_filename,
                    'snapshot' => ['incident' => $incident->attributesToArray(), 'attachment' => $attachment->toArray()],
                    'recorded_at' => now(),
                ])->save();
            });
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->files->discardUnreferenced($context->tenantId, $stored['object_key'], 'Rental incident attachment transaction failed.');
            }
            throw $exception;
        }
    }

    /** @return array{attachment: RentalIncidentAttachment, stream: resource} */
    public function open(AgreementContext $context, int $incidentId, int $attachmentId): array
    {
        $this->authorization->assertIncident($context, IncidentPermission::View);
        $this->contextValidation->assertContext($context);
        $incident = RentalIncident::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($incidentId);
        $attachment = $incident->attachments()->findOrFail($attachmentId);

        return ['attachment' => $attachment, 'stream' => $this->files->readStream($context->tenantId, $attachment->object_key)];
    }
}
