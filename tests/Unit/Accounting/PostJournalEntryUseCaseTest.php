<?php

namespace Tests\Unit\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Accounting\Application\UseCases\PostJournalEntryUseCase;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Events\JournalEntryPosted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PostJournalEntryUseCase.
 *
 * These tests verify the double-entry accounting balance rule and that
 * the domain event is dispatched upon successful posting.
 * No real DB or Laravel app is needed (Mockery only).
 */
class PostJournalEntryUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEntry(array $lines): object
    {
        $lineObjects = array_map(fn ($l) => (object) $l, $lines);

        return (object) [
            'id'     => 'je-uuid-1',
            'status' => 'draft',
            'lines'  => $lineObjects,
        ];
    }

    public function test_throws_when_entry_not_found(): void
    {
        $repo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        $useCase = new PostJournalEntryUseCase($repo);

        // Wrap DB::transaction to execute the closure directly
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(ModelNotFoundException::class);
        $useCase->execute(['id' => 'missing-id']);
    }

    public function test_throws_when_entry_is_not_balanced(): void
    {
        $entry = $this->makeEntry([
            ['debit' => '100.00000000', 'credit' => '0.00000000'],
            ['debit' => '0.00000000',  'credit' => '90.00000000'],  // 10 out of balance
        ]);

        $repo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($entry);

        $useCase = new PostJournalEntryUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not balanced/i');
        $useCase->execute(['id' => 'je-uuid-1']);
    }

    public function test_posts_balanced_entry_and_dispatches_event(): void
    {
        $entry = $this->makeEntry([
            ['debit' => '500.00000000', 'credit' => '0.00000000'],
            ['debit' => '0.00000000',  'credit' => '500.00000000'],
        ]);

        $posted = (object) ['id' => 'je-uuid-1', 'status' => 'posted'];

        $repo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($entry);
        $repo->shouldReceive('update')->with('je-uuid-1', ['status' => 'posted'])->andReturn($posted);

        $useCase = new PostJournalEntryUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof JournalEntryPosted);

        $result = $useCase->execute(['id' => 'je-uuid-1']);

        $this->assertSame('posted', $result->status);
    }

    public function test_balanced_entry_with_multiple_lines(): void
    {
        // Three debit lines and two credit lines that sum to the same amount
        $entry = $this->makeEntry([
            ['debit' => '200.00000000', 'credit' => '0.00000000'],
            ['debit' => '150.00000000', 'credit' => '0.00000000'],
            ['debit' => '50.00000000',  'credit' => '0.00000000'],
            ['debit' => '0.00000000',  'credit' => '300.00000000'],
            ['debit' => '0.00000000',  'credit' => '100.00000000'],
        ]);

        $posted = (object) ['id' => 'je-uuid-2', 'status' => 'posted'];

        $repo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($entry);
        $repo->shouldReceive('update')->andReturn($posted);

        $useCase = new PostJournalEntryUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $result = $useCase->execute(['id' => 'je-uuid-2']);
        $this->assertSame('posted', $result->status);
    }
}
