<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\Inventory\Events\StockMovementRecorded;
use Modules\Inventory\Listeners\UpdateAccountingValueListener;
use Tests\TestCase;

class UpdateAccountingValueListenerTest extends TestCase
{
    private $journalEntryRepository;

    private $journalEntryLineRepository;

    private $accountRepository;

    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalEntryRepository = Mockery::mock(JournalEntryRepositoryInterface::class);
        $this->journalEntryLineRepository = Mockery::mock(JournalEntryLineRepositoryInterface::class);
        $this->accountRepository = Mockery::mock(AccountRepositoryInterface::class);

        $this->listener = new UpdateAccountingValueListener(
            $this->journalEntryRepository,
            $this->journalEntryLineRepository,
            $this->accountRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_journal_entry_when_stock_movement_recorded_event_fires(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 100.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = 10;
        $movement->unit_cost = 100.00;
        $movement->movement_type = 'RECEIPT';
        $movement->reference_number = 'GR-20240209-001';
        $movement->created_at = now();
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $inventoryAccount = Mockery::mock();
        $inventoryAccount->id = 'inventory-account-uuid';

        $contraAccount = Mockery::mock();
        $contraAccount->id = 'contra-account-uuid';

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('1400')
            ->andReturn($inventoryAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('2100')
            ->andReturn($contraAccount);

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($movement) {
                return $data['tenant_id'] === $movement->tenant_id
                    && $data['entry_type'] === 'inventory_adjustment'
                    && $data['reference_type'] === 'stock_movement'
                    && $data['reference_id'] === $movement->id
                    && $data['status'] === 'posted';
            })
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(Mockery::mock());

        $event = new StockMovementRecorded($movement);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_creates_correct_debit_credit_entries_for_stock_increase(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 100.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = 10; // Positive quantity = stock increase
        $movement->unit_cost = 100.00;
        $movement->movement_type = 'RECEIPT';
        $movement->reference_number = 'GR-20240209-001';
        $movement->created_at = now();
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $inventoryAccount = Mockery::mock();
        $inventoryAccount->id = 'inventory-account-uuid';

        $contraAccount = Mockery::mock();
        $contraAccount->id = 'contra-account-uuid';

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->andReturn($inventoryAccount, $contraAccount);

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        // Expect debit to inventory asset account
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($inventoryAccount) {
                return $data['account_id'] === $inventoryAccount->id
                    && $data['debit_amount'] === 1000.00 // 10 * 100
                    && $data['credit_amount'] === 0;
            })
            ->andReturn(Mockery::mock());

        // Expect credit to contra account
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($contraAccount) {
                return $data['account_id'] === $contraAccount->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === 1000.00;
            })
            ->andReturn(Mockery::mock());

        $event = new StockMovementRecorded($movement);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_creates_correct_debit_credit_entries_for_stock_decrease(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 100.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = -10; // Negative quantity = stock decrease
        $movement->unit_cost = 100.00;
        $movement->movement_type = 'ISSUE';
        $movement->reference_number = 'SI-20240209-001';
        $movement->created_at = now();
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $inventoryAccount = Mockery::mock();
        $inventoryAccount->id = 'inventory-account-uuid';

        $contraAccount = Mockery::mock();
        $contraAccount->id = 'contra-account-uuid';

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->andReturn($inventoryAccount, $contraAccount);

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        // Expect debit to contra account (COGS)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($contraAccount) {
                return $data['account_id'] === $contraAccount->id
                    && $data['debit_amount'] === 1000.00 // abs(-10) * 100
                    && $data['credit_amount'] === 0;
            })
            ->andReturn(Mockery::mock());

        // Expect credit to inventory asset account
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($inventoryAccount) {
                return $data['account_id'] === $inventoryAccount->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === 1000.00;
            })
            ->andReturn(Mockery::mock());

        $event = new StockMovementRecorded($movement);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_handles_different_movement_types(): void
    {
        // Test RECEIPT movement type
        $this->assertMovementTypeHandled('RECEIPT', '2100');

        // Test ISSUE movement type
        $this->assertMovementTypeHandled('ISSUE', '5000');

        // Test ADJUSTMENT movement type
        $this->assertMovementTypeHandled('ADJUSTMENT', '6100');

        // Test RETURN movement type
        $this->assertMovementTypeHandled('RETURN', '2100');

        // Test TRANSFER movement type
        $this->assertMovementTypeHandled('TRANSFER', '1400');
    }

    private function assertMovementTypeHandled(string $movementType, string $expectedContraAccountCode): void
    {
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 100.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = 10;
        $movement->unit_cost = 100.00;
        $movement->movement_type = $movementType;
        $movement->reference_number = 'REF-001';
        $movement->created_at = now();
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $inventoryAccount = Mockery::mock();
        $inventoryAccount->id = 'inventory-account-uuid';

        $contraAccount = Mockery::mock();
        $contraAccount->id = 'contra-account-uuid';

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('1400')
            ->andReturn($inventoryAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with($expectedContraAccountCode)
            ->andReturn($contraAccount);

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(Mockery::mock());

        $event = new StockMovementRecorded($movement);

        $this->listener->handle($event);
    }

    public function test_it_skips_movements_that_should_not_create_journal_entries(): void
    {
        // Movements with type 'RESERVE' should not create journal entries
        $product = Mockery::mock();
        $product->name = 'Test Product';

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->movement_type = 'RESERVE'; // Should be skipped
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        // Should NOT create journal entry
        $this->journalEntryRepository
            ->shouldReceive('create')
            ->never();

        $event = new StockMovementRecorded($movement);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_rolls_back_transaction_on_failure(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();
        Log::shouldReceive('error')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 100.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = 10;
        $movement->unit_cost = 100.00;
        $movement->movement_type = 'RECEIPT';
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->andThrow(new \Exception('Database error'));

        $event = new StockMovementRecorded($movement);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->listener->handle($event);
    }

    public function test_it_creates_inventory_account_if_not_exists(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 100.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = 10;
        $movement->unit_cost = 100.00;
        $movement->movement_type = 'RECEIPT';
        $movement->reference_number = 'GR-001';
        $movement->created_at = now();
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $newInventoryAccount = Mockery::mock();
        $newInventoryAccount->id = 'new-inventory-account-uuid';

        $contraAccount = Mockery::mock();
        $contraAccount->id = 'contra-account-uuid';

        // First call returns null (account doesn't exist)
        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('1400')
            ->andReturn(null);

        // Account creation
        $this->accountRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($movement) {
                return $data['tenant_id'] === $movement->tenant_id
                    && $data['code'] === '1400'
                    && $data['account_type'] === 'asset'
                    && $data['account_subtype'] === 'current_asset'
                    && $data['is_system'] === true;
            })
            ->andReturn($newInventoryAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('2100')
            ->andReturn($contraAccount);

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(Mockery::mock());

        $event = new StockMovementRecorded($movement);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_uses_product_cost_price_when_unit_cost_not_set(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $product = Mockery::mock();
        $product->name = 'Test Product';
        $product->cost_price = 150.00;

        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';
        $movement->tenant_id = 'tenant-uuid-123';
        $movement->product_id = 'product-uuid-123';
        $movement->quantity = 10;
        $movement->unit_cost = null; // No unit cost
        $movement->movement_type = 'RECEIPT';
        $movement->reference_number = 'GR-001';
        $movement->created_at = now();
        $movement->shouldReceive('getAttribute')->with('product')->andReturn($product);

        $inventoryAccount = Mockery::mock();
        $inventoryAccount->id = 'inventory-account-uuid';

        $contraAccount = Mockery::mock();
        $contraAccount->id = 'contra-account-uuid';

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->andReturn($inventoryAccount, $contraAccount);

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        // Verify total value uses product cost_price
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->twice()
            ->withArgs(function ($data) {
                // Total value should be 10 * 150 = 1500
                return ($data['debit_amount'] === 1500.00 && $data['credit_amount'] === 0)
                    || ($data['debit_amount'] === 0 && $data['credit_amount'] === 1500.00);
            })
            ->andReturn(Mockery::mock());

        $event = new StockMovementRecorded($movement);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $movement = Mockery::mock();
        $movement->id = 'movement-uuid-123';

        $exception = new \Exception('Permanent failure');
        $event = new StockMovementRecorded($movement);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($movement, $exception) {
                return $message === 'Accounting journal entry creation failed permanently'
                    && $context['movement_id'] === $movement->id
                    && $context['error'] === $exception->getMessage();
            });

        // Act
        $this->listener->failed($event, $exception);

        // Assert
        $this->assertTrue(true);
    }
}
