<?php

namespace Tests\Unit\Helpdesk;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Helpdesk\Application\UseCases\ArchiveKbArticleUseCase;
use Modules\Helpdesk\Application\UseCases\CreateKbArticleUseCase;
use Modules\Helpdesk\Application\UseCases\PublishKbArticleUseCase;
use Modules\Helpdesk\Application\UseCases\RateKbArticleUseCase;
use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;
use Modules\Helpdesk\Domain\Events\KbArticleArchived;
use Modules\Helpdesk\Domain\Events\KbArticlePublished;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Helpdesk Knowledge Base use cases.
 *
 * Covers article creation, publish/archive lifecycle guards, rating validation,
 * and domain event assertions.
 */
class KnowledgeBaseUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeArticle(string $status = 'draft', int $helpfulCount = 0, int $notHelpfulCount = 0): object
    {
        return (object) [
            'id'               => 'article-uuid-1',
            'tenant_id'        => 'tenant-uuid-1',
            'title'            => 'How to reset your password',
            'body'             => 'Follow these steps...',
            'visibility'       => 'agents_only',
            'status'           => $status,
            'helpful_count'    => $helpfulCount,
            'not_helpful_count' => $notHelpfulCount,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateKbArticleUseCase
    // -------------------------------------------------------------------------

    public function test_create_article_sets_status_draft(): void
    {
        $article     = $this->makeArticle('draft');
        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);

        $articleRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) => $d['status'] === 'draft' && $d['title'] === 'How to reset your password')
            ->andReturn($article);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $result = (new CreateKbArticleUseCase($articleRepo))->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'title'      => 'How to reset your password',
            'body'       => 'Follow these steps...',
        ]);

        $this->assertSame('draft', $result->status);
    }

    // -------------------------------------------------------------------------
    // PublishKbArticleUseCase
    // -------------------------------------------------------------------------

    public function test_publish_article_transitions_to_published_and_dispatches_event(): void
    {
        $draft     = $this->makeArticle('draft');
        $published = $this->makeArticle('published');

        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->with('article-uuid-1')->andReturn($draft);
        $articleRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $d) => $id === 'article-uuid-1' && $d['status'] === 'published')
            ->andReturn($published);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof KbArticlePublished
                && $event->articleId === 'article-uuid-1');

        $result = (new PublishKbArticleUseCase($articleRepo))->execute('article-uuid-1');

        $this->assertSame('published', $result->status);
    }

    public function test_publish_already_published_article_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already published');

        $published   = $this->makeArticle('published');
        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($published);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        (new PublishKbArticleUseCase($articleRepo))->execute('article-uuid-1');
    }

    public function test_publish_archived_article_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Archived articles cannot be published');

        $archived    = $this->makeArticle('archived');
        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($archived);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        (new PublishKbArticleUseCase($articleRepo))->execute('article-uuid-1');
    }

    public function test_publish_not_found_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not found');

        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn(null);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        (new PublishKbArticleUseCase($articleRepo))->execute('article-uuid-1');
    }

    // -------------------------------------------------------------------------
    // ArchiveKbArticleUseCase
    // -------------------------------------------------------------------------

    public function test_archive_article_transitions_to_archived_and_dispatches_event(): void
    {
        $published = $this->makeArticle('published');
        $archived  = $this->makeArticle('archived');

        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($published);
        $articleRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $d) => $id === 'article-uuid-1' && $d['status'] === 'archived')
            ->andReturn($archived);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof KbArticleArchived
                && $event->articleId === 'article-uuid-1');

        $result = (new ArchiveKbArticleUseCase($articleRepo))->execute('article-uuid-1');

        $this->assertSame('archived', $result->status);
    }

    public function test_archive_already_archived_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already archived');

        $archived    = $this->makeArticle('archived');
        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($archived);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        (new ArchiveKbArticleUseCase($articleRepo))->execute('article-uuid-1');
    }

    // -------------------------------------------------------------------------
    // RateKbArticleUseCase
    // -------------------------------------------------------------------------

    public function test_rate_helpful_increments_helpful_count(): void
    {
        $published = $this->makeArticle('published', 5, 1);
        $updated   = $this->makeArticle('published', 6, 1);

        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($published);
        $articleRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $d) => $id === 'article-uuid-1' && $d['helpful_count'] === 6)
            ->andReturn($updated);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $result = (new RateKbArticleUseCase($articleRepo))->execute('article-uuid-1', true);

        $this->assertSame(6, $result->helpful_count);
    }

    public function test_rate_not_helpful_increments_not_helpful_count(): void
    {
        $published = $this->makeArticle('published', 5, 1);
        $updated   = $this->makeArticle('published', 5, 2);

        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($published);
        $articleRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $d) => $id === 'article-uuid-1' && $d['not_helpful_count'] === 2)
            ->andReturn($updated);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $result = (new RateKbArticleUseCase($articleRepo))->execute('article-uuid-1', false);

        $this->assertSame(2, $result->not_helpful_count);
    }

    public function test_rate_draft_article_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only published articles can be rated');

        $draft       = $this->makeArticle('draft');
        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn($draft);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        (new RateKbArticleUseCase($articleRepo))->execute('article-uuid-1', true);
    }

    public function test_rate_not_found_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not found');

        $articleRepo = Mockery::mock(KbArticleRepositoryInterface::class);
        $articleRepo->shouldReceive('findById')->once()->andReturn(null);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        (new RateKbArticleUseCase($articleRepo))->execute('article-uuid-1', true);
    }
}
