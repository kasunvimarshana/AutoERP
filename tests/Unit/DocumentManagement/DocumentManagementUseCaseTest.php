<?php

namespace Tests\Unit\DocumentManagement;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\DocumentManagement\Application\UseCases\ArchiveDocumentUseCase;
use Modules\DocumentManagement\Application\UseCases\CreateDocumentUseCase;
use Modules\DocumentManagement\Application\UseCases\PublishDocumentUseCase;
use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;
use Modules\DocumentManagement\Domain\Events\DocumentArchived;
use Modules\DocumentManagement\Domain\Events\DocumentPublished;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Document Management module use cases.
 *
 * Covers document creation with draft status, publish lifecycle guards,
 * and archive lifecycle guards with domain event dispatch.
 */
class DocumentManagementUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeDocument(string $status = 'draft'): object
    {
        return (object) [
            'id'        => 'doc-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Employee Handbook',
            'status'    => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateDocumentUseCase
    // -------------------------------------------------------------------------

    public function test_create_document_sets_status_draft(): void
    {
        $document     = $this->makeDocument('draft');
        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);

        $documentRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft' && $data['title'] === 'Employee Handbook')
            ->andReturn($document);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateDocumentUseCase($documentRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Employee Handbook',
            'owner_id'  => 'user-uuid-1',
        ]);

        $this->assertSame('draft', $result->status);
    }

    // -------------------------------------------------------------------------
    // PublishDocumentUseCase
    // -------------------------------------------------------------------------

    public function test_publish_throws_when_document_not_found(): void
    {
        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new PublishDocumentUseCase($documentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_publish_throws_when_already_published(): void
    {
        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn($this->makeDocument('published'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new PublishDocumentUseCase($documentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already published/i');

        $useCase->execute('doc-uuid-1');
    }

    public function test_publish_throws_when_archived(): void
    {
        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn($this->makeDocument('archived'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new PublishDocumentUseCase($documentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/archived/i');

        $useCase->execute('doc-uuid-1');
    }

    public function test_publish_transitions_to_published_and_dispatches_event(): void
    {
        $draft     = $this->makeDocument('draft');
        $published = (object) array_merge((array) $draft, ['status' => 'published']);

        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn($draft);
        $documentRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'published')
            ->once()
            ->andReturn($published);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof DocumentPublished
                && $event->documentId === 'doc-uuid-1'
                && $event->title === 'Employee Handbook');

        $useCase = new PublishDocumentUseCase($documentRepo);
        $result  = $useCase->execute('doc-uuid-1');

        $this->assertSame('published', $result->status);
    }

    // -------------------------------------------------------------------------
    // ArchiveDocumentUseCase
    // -------------------------------------------------------------------------

    public function test_archive_throws_when_document_not_found(): void
    {
        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ArchiveDocumentUseCase($documentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_archive_throws_when_already_archived(): void
    {
        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn($this->makeDocument('archived'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ArchiveDocumentUseCase($documentRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already archived/i');

        $useCase->execute('doc-uuid-1');
    }

    public function test_archive_transitions_to_archived_and_dispatches_event(): void
    {
        $published = $this->makeDocument('published');
        $archived  = (object) array_merge((array) $published, ['status' => 'archived']);

        $documentRepo = Mockery::mock(DocumentRepositoryInterface::class);
        $documentRepo->shouldReceive('findById')->andReturn($published);
        $documentRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'archived')
            ->once()
            ->andReturn($archived);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof DocumentArchived
                && $event->documentId === 'doc-uuid-1');

        $useCase = new ArchiveDocumentUseCase($documentRepo);
        $result  = $useCase->execute('doc-uuid-1');

        $this->assertSame('archived', $result->status);
    }
}
