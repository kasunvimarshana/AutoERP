<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\IncidentReviewAction;
use Modules\VehicleRental\Models\RentalIncidentAttachment;
use Modules\VehicleRental\Models\RentalIncidentEvent;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalIncidentAttachmentService;
use Modules\VehicleRental\Services\RentalIncidentService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class RentalIncidentAttachmentTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('tenant_private');
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertIncident')->zeroOrMoreTimes();
        });
    }

    public function test_private_file_is_immutable_versioned_and_audited_without_financial_effects(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $incident = $this->incident($context, $use);
            $service = app(RentalIncidentAttachmentService::class);
            $file = $this->pdf();
            $bytes = file_get_contents($file->getPathname());
            $service->attach($context, $incident->id, 1, $file);
            $attachment = $incident->attachments()->firstOrFail();
            self::assertSame(2, $incident->refresh()->row_version);
            self::assertSame(hash('sha256', $bytes), $attachment->checksum_sha256);
            self::assertSame(strlen($bytes), (int) $attachment->size_bytes);
            self::assertArrayNotHasKey('object_key', $attachment->toArray());
            $opened = $service->open($context, $incident->id, $attachment->id);
            self::assertSame($bytes, stream_get_contents($opened['stream']));
            fclose($opened['stream']);
            $event = $incident->events()->reorder()->latest('id')->firstOrFail();
            self::assertSame('attachment_added', $event->action);
            self::assertSame(2, $event->snapshot['incident']['row_version']);
            self::assertArrayNotHasKey('object_key', $event->snapshot['attachment']);
            foreach (['invoices', 'payments', 'finance_journal_entries'] as $table) {
                self::assertSame(0, DB::table($table)->count());
            }
            foreach (['save', 'delete'] as $operation) {
                try {
                    if ($operation === 'save') {
                        $attachment->forceFill(['original_filename' => 'replacement.pdf']);
                    }
                    $attachment->$operation();
                    self::fail('Evidence history was mutated.');
                } catch (LogicException) {
                    self::assertSame('receipt.pdf', $attachment->refresh()->original_filename);
                }
            }
            app(RentalIncidentService::class)->review($context, $incident->id, 2, IncidentReviewAction::Confirm, 'Receipt verified');
            $this->expectException(ConflictHttpException::class);
            $service->attach($context, $incident->id, 3, $this->pdf('different.pdf', 'Other evidence'));
        });
    }

    public function test_stale_duplicate_and_foreign_organization_files_are_rejected_without_changes(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $incident = $this->incident($context, $use);
            $service = app(RentalIncidentAttachmentService::class);
            $service->attach($context, $incident->id, 1, $this->pdf());
            foreach ([1 => ConflictHttpException::class, 2 => ValidationException::class] as $version => $exceptionClass) {
                try {
                    $service->attach($context, $incident->id, $version, $this->pdf());
                    self::fail('Duplicate or stale evidence accepted.');
                } catch (ConflictHttpException|ValidationException $exception) {
                    self::assertInstanceOf($exceptionClass, $exception);
                }
            }
            self::assertSame(1, $incident->attachments()->count());
            self::assertCount(1, Storage::disk('tenant_private')->allFiles());
            self::assertSame(2, $incident->refresh()->row_version);
            $otherId = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'PROOF-OTHER', 'name' => 'Other branch']);
            $other = new AgreementContext($context->tenantId, $otherId, $context->actorId);
            $this->expectException(ModelNotFoundException::class);
            $service->open($other, $incident->id, $incident->attachments()->firstOrFail()->id);
        });
    }

    public function test_invalid_content_and_oversize_are_rejected_before_storage(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $incident = $this->incident($context, $use);
            foreach ([UploadedFile::fake()->createWithContent('receipt.pdf', '<script>bad</script>'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
                try {
                    app(RentalIncidentAttachmentService::class)->attach($context, $incident->id, 1, $file);
                    self::fail('Invalid file was accepted.');
                } catch (ValidationException $exception) {
                    self::assertArrayHasKey('file', $exception->errors());
                }
            }
            self::assertSame(0, RentalIncidentAttachment::query()->count());
            self::assertSame(1, $incident->refresh()->row_version);
            self::assertCount(0, Storage::disk('tenant_private')->allFiles());
        });
    }

    public function test_event_failure_rolls_back_metadata_version_and_private_object(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $incident = $this->incident($context, $use);
            RentalIncidentEvent::creating(function (): void {
                throw new RuntimeException('Injected audit failure');
            });
            try {
                app(RentalIncidentAttachmentService::class)->attach($context, $incident->id, 1, $this->pdf());
                self::fail('Audit failure was ignored.');
            } catch (RuntimeException $exception) {
                self::assertSame('Injected audit failure', $exception->getMessage());
            } finally {
                RentalIncidentEvent::flushEventListeners();
                RentalIncidentEvent::clearBootedModels();
            }
            self::assertSame(0, RentalIncidentAttachment::query()->count());
            self::assertSame(1, $incident->refresh()->row_version);
            self::assertSame(1, $incident->events()->count());
            self::assertCount(0, Storage::disk('tenant_private')->allFiles());
        });
    }

    private function incident($context, $use)
    {
        return app(RentalIncidentService::class)->create($context, [
            'vehicle_use_id' => $use->id, 'expected_use_version' => $use->row_version,
            'incident_type' => 'fuel', 'occurred_on' => '2026-09-07',
            'evidence_reference' => 'Receipt', 'description' => 'Fuel evidence',
        ]);
    }

    private function pdf(string $name = 'receipt.pdf', string $content = 'Receipt proof'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".$content."\n%%EOF");
    }
}
