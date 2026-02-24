<?php

namespace Tests\Unit\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Integration\Application\UseCases\CreateApiKeyUseCase;
use Modules\Integration\Application\UseCases\CreateWebhookUseCase;
use Modules\Integration\Domain\Contracts\ApiKeyRepositoryInterface;
use Modules\Integration\Domain\Contracts\WebhookRepositoryInterface;
use Modules\Integration\Domain\Events\ApiKeyCreated;
use Modules\Integration\Domain\Events\WebhookCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Integration module use cases.
 *
 * Covers webhook creation, API key creation (hashed key), domain event assertions.
 */
class IntegrationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateWebhookUseCase
    // -------------------------------------------------------------------------

    public function test_create_webhook_dispatches_event(): void
    {
        $webhook = (object) [
            'id'        => 'webhook-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Order Webhook',
            'url'       => 'https://example.com/hook',
            'is_active' => true,
        ];

        $repo = Mockery::mock(WebhookRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['url'] === 'https://example.com/hook' && $data['is_active'] === true)
            ->andReturn($webhook);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof WebhookCreated && $e->url === 'https://example.com/hook');

        $useCase = new CreateWebhookUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Order Webhook',
            'url'       => 'https://example.com/hook',
        ]);

        $this->assertTrue($result->is_active);
    }

    // -------------------------------------------------------------------------
    // CreateApiKeyUseCase
    // -------------------------------------------------------------------------

    public function test_create_api_key_hashes_key_and_dispatches_event(): void
    {
        $repo = Mockery::mock(ApiKeyRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // key_hash must be a 64-char hex SHA-256 digest
                return strlen($data['key_hash']) === 64
                    && str_starts_with($data['key_prefix'], 'kv_')
                    && $data['is_active'] === true;
            })
            ->andReturnUsing(function ($data) {
                return (object) array_merge(['id' => 'apikey-uuid-1'], $data);
            });

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof ApiKeyCreated);

        $useCase = new CreateApiKeyUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Zapier Key',
        ]);

        $this->assertObjectHasProperty('plain_key', $result);
        $this->assertStringStartsWith('kv_', $result->plain_key);
        $this->assertSame(hash('sha256', $result->plain_key), $result->key_hash);
    }
}
