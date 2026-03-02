<?php

declare(strict_types=1);

namespace Modules\POS\Tests\Unit;

use Modules\POS\Application\DTOs\CreatePOSTransactionDTO;
use Modules\POS\Application\Services\POSService;
use Modules\POS\Domain\Contracts\POSRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for POSService.
 *
 * createTransaction(), voidTransaction(), and syncOfflineTransactions()
 * call DB::transaction() internally, which requires a full Laravel bootstrap.
 * These pure-PHP tests verify method signatures and structural contracts.
 */
class POSServiceStructureTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_pos_service_has_create_transaction_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'createTransaction'),
            'POSService must expose a public createTransaction() method.'
        );
    }

    public function test_pos_service_has_void_transaction_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'voidTransaction'),
            'POSService must expose a public voidTransaction() method.'
        );
    }

    public function test_pos_service_has_sync_offline_transactions_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'syncOfflineTransactions'),
            'POSService must expose a public syncOfflineTransactions() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_transaction_accepts_create_pos_transaction_dto(): void
    {
        $reflection = new \ReflectionMethod(POSService::class, 'createTransaction');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreatePOSTransactionDTO::class, (string) $params[0]->getType());
    }

    public function test_void_transaction_accepts_transaction_id(): void
    {
        $reflection = new \ReflectionMethod(POSService::class, 'voidTransaction');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('transactionId', $params[0]->getName());
    }

    public function test_sync_offline_transactions_accepts_transaction_ids_array(): void
    {
        $reflection = new \ReflectionMethod(POSService::class, 'syncOfflineTransactions');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('transactionIds', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // Return types — reflection
    // -------------------------------------------------------------------------

    public function test_sync_offline_transactions_return_type_is_array(): void
    {
        $reflection = new \ReflectionMethod(POSService::class, 'syncOfflineTransactions');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('array', $returnType);
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_pos_service_instantiates_with_repository_contract(): void
    {
        $repo    = $this->createMock(POSRepositoryContract::class);
        $service = new POSService($repo);

        $this->assertInstanceOf(POSService::class, $service);
    }

    // -------------------------------------------------------------------------
    // CreatePOSTransactionDTO — payload field mapping
    // -------------------------------------------------------------------------

    public function test_dto_session_id_and_offline_flag_map_correctly(): void
    {
        $dto = CreatePOSTransactionDTO::fromArray([
            'session_id'      => 12,
            'is_offline'      => true,
            'discount_amount' => '5.0000',
            'lines'           => [],
            'payments'        => [],
        ]);

        $this->assertSame(12, $dto->sessionId);
        $this->assertTrue($dto->isOffline);
        $this->assertSame('5.0000', $dto->discountAmount);
    }
}
