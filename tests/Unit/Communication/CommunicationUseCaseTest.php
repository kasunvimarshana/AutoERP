<?php

namespace Tests\Unit\Communication;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Communication\Application\UseCases\CreateChannelUseCase;
use Modules\Communication\Application\UseCases\SendMessageUseCase;
use Modules\Communication\Domain\Contracts\ChannelRepositoryInterface;
use Modules\Communication\Domain\Contracts\MessageRepositoryInterface;
use Modules\Communication\Domain\Events\ChannelCreated;
use Modules\Communication\Domain\Events\MessageSent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Communication module use cases.
 *
 * Covers channel creation, message sending, and domain event assertions.
 */
class CommunicationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateChannelUseCase
    // -------------------------------------------------------------------------

    public function test_create_channel_dispatches_event(): void
    {
        $channel = (object) [
            'id'        => 'chan-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'engineering',
            'type'      => 'channel',
            'created_by'=> 'user-uuid-1',
        ];

        $repo = Mockery::mock(ChannelRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'channel' && $data['name'] === 'engineering')
            ->andReturn($channel);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof ChannelCreated && $e->name === 'engineering');

        $useCase = new CreateChannelUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'engineering',
            'type'       => 'channel',
            'created_by' => 'user-uuid-1',
        ]);

        $this->assertSame('channel', $result->type);
    }

    public function test_create_direct_channel(): void
    {
        $channel = (object) [
            'id'        => 'chan-uuid-2',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'DM: Alice <> Bob',
            'type'      => 'direct',
            'created_by'=> 'user-uuid-1',
        ];

        $repo = Mockery::mock(ChannelRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'direct')
            ->andReturn($channel);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateChannelUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'DM: Alice <> Bob',
            'type'       => 'direct',
            'created_by' => 'user-uuid-1',
        ]);

        $this->assertSame('direct', $result->type);
    }

    // -------------------------------------------------------------------------
    // SendMessageUseCase
    // -------------------------------------------------------------------------

    public function test_send_message_dispatches_event(): void
    {
        $message = (object) [
            'id'         => 'msg-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'channel_id' => 'chan-uuid-1',
            'sender_id'  => 'user-uuid-1',
            'body'       => 'Hello, team!',
            'type'       => 'text',
        ];

        $repo = Mockery::mock(MessageRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['body'] === 'Hello, team!' && $data['type'] === 'text')
            ->andReturn($message);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof MessageSent
                && $e->channelId === 'chan-uuid-1'
                && $e->senderId === 'user-uuid-1');

        $useCase = new SendMessageUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'channel_id' => 'chan-uuid-1',
            'sender_id'  => 'user-uuid-1',
            'body'       => 'Hello, team!',
        ]);

        $this->assertSame('Hello, team!', $result->body);
    }
}
