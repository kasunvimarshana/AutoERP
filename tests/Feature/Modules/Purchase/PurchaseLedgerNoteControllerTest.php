<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseLedgerNoteServiceInterface;
use Tests\TestCase;

final class PurchaseLedgerNoteControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_store_ledger_note_forwards_source_payload_to_service(): void
    {
        $service = $this->createMock(PurchaseLedgerNoteServiceInterface::class);
        $service->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return (int) ($payload['tenant_id'] ?? 0) === 1
                    && (string) ($payload['source_type'] ?? '') === 'purchase_order'
                    && (int) ($payload['source_id'] ?? 0) === 10
                    && (string) ($payload['body'] ?? '') === 'Submitted for approval.';
            }))
            ->willReturn(Result::success([
                'id' => 55,
                'source_type' => 'purchase_order',
                'source_id' => 10,
            ]));

        $this->app->instance(PurchaseLedgerNoteServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/ledger-notes', [
            'tenant_id' => 1,
            'source_type' => 'purchase_order',
            'source_id' => 10,
            'note_type' => 'workflow',
            'body' => 'Submitted for approval.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', 55);
    }
}
