<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use InvalidArgumentException;
use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryService pharmaceutical compliance validation.
 *
 * These tests exercise the pure-PHP validation logic that runs before
 * any database interaction. No database or Laravel bootstrap required.
 *
 * Per KB.md §11.8 and AGENT.md §PHARMACEUTICAL COMPLIANCE MODE:
 *   - batch_number is mandatory when pharmaceutical compliance mode is enabled
 *   - expiry_date is mandatory when pharmaceutical compliance mode is enabled
 */
class InventoryServiceValidationTest extends TestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $repo          = $this->createStub(InventoryRepositoryContract::class);
        $this->service = new InventoryService($repo);
    }

    // -------------------------------------------------------------------------
    // Pharmaceutical compliance: batch_number required
    // -------------------------------------------------------------------------

    public function test_pharma_compliant_without_batch_number_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/batch_number/i');

        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'purchase_receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 1,
            'uom_id'                     => 1,
            'quantity'                   => '10.0000',
            'unit_cost'                  => '5.0000',
            'expiry_date'                => '2027-12-31',
            'is_pharmaceutical_compliant' => true,
            // batch_number intentionally omitted
        ]);

        $this->service->recordTransaction($dto);
    }

    // -------------------------------------------------------------------------
    // Pharmaceutical compliance: expiry_date required
    // -------------------------------------------------------------------------

    public function test_pharma_compliant_without_expiry_date_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/expiry_date/i');

        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'purchase_receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 1,
            'uom_id'                     => 1,
            'quantity'                   => '10.0000',
            'unit_cost'                  => '5.0000',
            'batch_number'               => 'BATCH-001',
            'is_pharmaceutical_compliant' => true,
            // expiry_date intentionally omitted
        ]);

        $this->service->recordTransaction($dto);
    }

    public function test_pharma_compliant_with_empty_expiry_date_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/expiry_date/i');

        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'purchase_receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 1,
            'uom_id'                     => 1,
            'quantity'                   => '10.0000',
            'unit_cost'                  => '5.0000',
            'batch_number'               => 'BATCH-001',
            'expiry_date'                => '',
            'is_pharmaceutical_compliant' => true,
        ]);

        $this->service->recordTransaction($dto);
    }

    public function test_pharma_compliant_with_empty_batch_number_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/batch_number/i');

        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'purchase_receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 1,
            'uom_id'                     => 1,
            'quantity'                   => '10.0000',
            'unit_cost'                  => '5.0000',
            'batch_number'               => '',
            'expiry_date'                => '2027-12-31',
            'is_pharmaceutical_compliant' => true,
        ]);

        $this->service->recordTransaction($dto);
    }

    // -------------------------------------------------------------------------
    // Non-pharmaceutical: validation does not run
    // -------------------------------------------------------------------------

    public function test_non_pharma_transaction_without_batch_does_not_throw_validation_error(): void
    {
        // Without pharma compliance, missing batch/expiry should not raise
        // an InvalidArgumentException from the validation block.
        // (The DB::transaction call will fail in this pure-PHP context,
        //  but that is not an InvalidArgumentException.)
        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'purchase_receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 1,
            'uom_id'                     => 1,
            'quantity'                   => '10.0000',
            'unit_cost'                  => '5.0000',
            'is_pharmaceutical_compliant' => false,
            // batch_number and expiry_date intentionally omitted
        ]);

        $validationException = null;
        try {
            $this->service->recordTransaction($dto);
        } catch (InvalidArgumentException $e) {
            $validationException = $e;
        } catch (\Throwable) {
            // Other exceptions (e.g., DB not bootstrapped) are expected and OK.
        }

        $this->assertNull(
            $validationException,
            'Non-pharmaceutical transactions must not fail pharmaceutical compliance validation.'
        );
    }

    // -------------------------------------------------------------------------
    // StockTransactionDTO: pharmaceutical flag defaults to false
    // -------------------------------------------------------------------------

    public function test_dto_pharma_compliant_defaults_to_false(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type' => 'purchase_receipt',
            'warehouse_id'     => 1,
            'product_id'       => 1,
            'uom_id'           => 1,
            'quantity'         => '5.0000',
            'unit_cost'        => '2.0000',
        ]);

        $this->assertFalse($dto->isPharmaceuticalCompliant);
    }

    public function test_dto_pharma_compliant_can_be_set_to_true(): void
    {
        $dto = StockTransactionDTO::fromArray([
            'transaction_type'           => 'purchase_receipt',
            'warehouse_id'               => 1,
            'product_id'                 => 1,
            'uom_id'                     => 1,
            'quantity'                   => '5.0000',
            'unit_cost'                  => '2.0000',
            'is_pharmaceutical_compliant' => true,
        ]);

        $this->assertTrue($dto->isPharmaceuticalCompliant);
    }
}
