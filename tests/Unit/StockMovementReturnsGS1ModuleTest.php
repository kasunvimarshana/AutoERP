<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// StockMovement
use Modules\StockMovement\Domain\ValueObjects\MovementType;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\Events\StockMovementCreated;
use Modules\StockMovement\Domain\Events\StockMovementConfirmed;
use Modules\StockMovement\Domain\Exceptions\StockMovementNotFoundException;
use Modules\StockMovement\Application\DTOs\StockMovementData;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\FindStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\ConfirmStockMovementServiceInterface;
use Modules\StockMovement\Application\Services\CreateStockMovementService;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\StockMovement\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockMovementRepository;
use Modules\StockMovement\Infrastructure\Http\Controllers\StockMovementController;
use Modules\StockMovement\Infrastructure\Http\Requests\StoreStockMovementRequest;
use Modules\StockMovement\Infrastructure\Http\Resources\StockMovementResource;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

// Returns
use Modules\Returns\Domain\ValueObjects\ReturnType;
use Modules\Returns\Domain\ValueObjects\ReturnStatus;
use Modules\Returns\Domain\ValueObjects\ReturnCondition;
use Modules\Returns\Domain\ValueObjects\ReturnDisposition;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\Events\StockReturnCreated;
use Modules\Returns\Domain\Events\StockReturnApproved;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Application\DTOs\StockReturnData;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\ApproveStockReturnServiceInterface;
use Modules\Returns\Infrastructure\Providers\ReturnsServiceProvider;

// GS1
use Modules\GS1\Domain\ValueObjects\Gs1IdentifierType;
use Modules\GS1\Domain\ValueObjects\BarcodeType;
use Modules\GS1\Domain\Entities\Gs1Identifier;
use Modules\GS1\Domain\Entities\Gs1Barcode;
use Modules\GS1\Domain\Events\Gs1IdentifierCreated;
use Modules\GS1\Domain\Events\Gs1BarcodeCreated;
use Modules\GS1\Domain\Exceptions\Gs1IdentifierNotFoundException;
use Modules\GS1\Domain\Exceptions\Gs1BarcodeNotFoundException;
use Modules\GS1\Application\DTOs\Gs1IdentifierData;
use Modules\GS1\Application\DTOs\Gs1BarcodeData;
use Modules\GS1\Application\Contracts\CreateGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\FindGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\CreateGs1BarcodeServiceInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1IdentifierModel;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1BarcodeModel;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGs1IdentifierRepository;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGs1BarcodeRepository;
use Modules\GS1\Infrastructure\Http\Controllers\Gs1IdentifierController;
use Modules\GS1\Infrastructure\Http\Requests\StoreGs1IdentifierRequest;
use Modules\GS1\Infrastructure\Http\Resources\Gs1IdentifierResource;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;

// Core base classes
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\Contracts\ReadServiceInterface;

class StockMovementReturnsGS1ModuleTest extends TestCase
{
    // ----------------------------------------------------------------
    // STOCK MOVEMENT SECTION
    // ----------------------------------------------------------------

    public function test_movement_type_values(): void
    {
        $values = MovementType::values();
        $this->assertContains('receipt', $values);
        $this->assertContains('issue', $values);
        $this->assertContains('transfer', $values);
        $this->assertContains('adjustment', $values);
        $this->assertContains('return_in', $values);
        $this->assertContains('return_out', $values);
    }

    public function test_movement_type_receipt_constant(): void
    {
        $this->assertSame('receipt', MovementType::RECEIPT);
    }

    public function test_stock_movement_entity_defaults(): void
    {
        $movement = new StockMovement(
            tenantId: 1,
            referenceNumber: 'SM-001',
            movementType: 'receipt',
            productId: 5,
            quantity: 10.0,
        );

        $this->assertEquals(1, $movement->getTenantId());
        $this->assertEquals('SM-001', $movement->getReferenceNumber());
        $this->assertEquals('draft', $movement->getStatus());
    }

    public function test_stock_movement_confirm(): void
    {
        $movement = new StockMovement(
            tenantId: 1,
            referenceNumber: 'SM-001',
            movementType: 'receipt',
            productId: 5,
            quantity: 10.0,
        );

        $movement->confirm();

        $this->assertEquals('confirmed', $movement->getStatus());
        $this->assertTrue($movement->isConfirmed());
    }

    public function test_stock_movement_cancel(): void
    {
        $movement = new StockMovement(
            tenantId: 1,
            referenceNumber: 'SM-001',
            movementType: 'receipt',
            productId: 5,
            quantity: 10.0,
        );

        $movement->cancel();

        $this->assertEquals('cancelled', $movement->getStatus());
        $this->assertTrue($movement->isCancelled());
    }

    public function test_stock_movement_created_event(): void
    {
        $movement = new StockMovement(
            tenantId: 1,
            referenceNumber: 'SM-001',
            movementType: 'receipt',
            productId: 5,
            quantity: 10.0,
        );

        $event = new StockMovementCreated($movement);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_stock_movement_confirmed_event(): void
    {
        $movement = new StockMovement(
            tenantId: 1,
            referenceNumber: 'SM-001',
            movementType: 'receipt',
            productId: 5,
            quantity: 10.0,
        );

        $event = new StockMovementConfirmed($movement);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_stock_movement_not_found_exception(): void
    {
        $exception = new StockMovementNotFoundException(42);
        $message = $exception->getMessage();

        $this->assertStringContainsString('StockMovement', $message);
        $this->assertStringContainsString('42', $message);
    }

    public function test_stock_movement_data_dto(): void
    {
        $this->assertTrue(is_a(StockMovementData::class, BaseDto::class, true));
    }

    public function test_create_stock_movement_service_interface(): void
    {
        $this->assertTrue(is_a(CreateStockMovementServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_stock_movement_service_interface(): void
    {
        $this->assertTrue(is_a(FindStockMovementServiceInterface::class, ReadServiceInterface::class, true));
    }

    public function test_confirm_stock_movement_service_interface(): void
    {
        $this->assertTrue(is_a(ConfirmStockMovementServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_create_service_extends_base(): void
    {
        $this->assertTrue(is_a(CreateStockMovementService::class, BaseService::class, true));
    }

    public function test_stock_movement_model_table(): void
    {
        $model = new StockMovementModel;
        $this->assertEquals('stock_movements', $model->getTable());
    }

    public function test_stock_movement_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(EloquentStockMovementRepository::class, StockMovementRepositoryInterface::class, true));
    }

    public function test_stock_movement_controller_injects_find_service(): void
    {
        $rc          = new \ReflectionClass(StockMovementController::class);
        $constructor = $rc->getConstructor();
        $params      = $constructor->getParameters();
        $types       = array_map(
            fn (\ReflectionParameter $p) => $p->getType() instanceof \ReflectionNamedType
                ? $p->getType()->getName()
                : null,
            $params
        );

        $this->assertContains(FindStockMovementServiceInterface::class, $types);
    }

    public function test_store_request_has_rules(): void
    {
        $request = new StoreStockMovementRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('movement_type', $rules);
    }

    public function test_stock_movement_resource_returns_array(): void
    {
        $movement = new StockMovement(
            tenantId: 1,
            referenceNumber: 'SM-001',
            movementType: 'receipt',
            productId: 5,
            quantity: 10.0,
        );

        $resource = new StockMovementResource($movement);
        $array    = $resource->toArray(null);

        $this->assertArrayHasKey('id', $array);
    }

    // ----------------------------------------------------------------
    // RETURNS SECTION
    // ----------------------------------------------------------------

    public function test_return_type_values(): void
    {
        $values = ReturnType::values();
        $this->assertContains('purchase_return', $values);
        $this->assertContains('sales_return', $values);
    }

    public function test_return_status_values(): void
    {
        $values = ReturnStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('approved', $values);
        $this->assertContains('completed', $values);
    }

    public function test_return_condition_values(): void
    {
        $values = ReturnCondition::values();
        $this->assertContains('good', $values);
        $this->assertContains('damaged', $values);
        $this->assertContains('defective', $values);
        $this->assertContains('expired', $values);
    }

    public function test_return_disposition_values(): void
    {
        $values = ReturnDisposition::values();
        $this->assertContains('restock', $values);
        $this->assertContains('scrap', $values);
        $this->assertContains('vendor_return', $values);
        $this->assertContains('quarantine', $values);
    }

    public function test_stock_return_entity_defaults(): void
    {
        $return = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $this->assertEquals('draft', $return->getStatus());
        $this->assertTrue($return->isPurchaseReturn());
    }

    public function test_stock_return_approve(): void
    {
        $returnEntity = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $returnEntity->approve(5);

        $this->assertEquals('approved', $returnEntity->getStatus());
        $this->assertEquals(5, $returnEntity->getApprovedBy());
    }

    public function test_stock_return_reject(): void
    {
        $returnEntity = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $returnEntity->reject();

        $this->assertEquals('rejected', $returnEntity->getStatus());
    }

    public function test_stock_return_complete(): void
    {
        $returnEntity = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $returnEntity->approve(5);
        $returnEntity->complete(3);

        $this->assertEquals('completed', $returnEntity->getStatus());
    }

    public function test_stock_return_issue_credit_memo(): void
    {
        $returnEntity = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $returnEntity->issueCreditMemo('CM-001');

        $this->assertTrue($returnEntity->getCreditMemoIssued());
        $this->assertEquals('CM-001', $returnEntity->getCreditMemoReference());
    }

    public function test_stock_return_line_entity(): void
    {
        $line = new StockReturnLine(
            tenantId: 1,
            stockReturnId: 1,
            productId: 5,
            quantityRequested: 3.0,
        );

        $this->assertEquals('good', $line->getCondition());
        $this->assertEquals('pending', $line->getQualityCheckStatus());
    }

    public function test_stock_return_line_pass_quality(): void
    {
        $line = new StockReturnLine(
            tenantId: 1,
            stockReturnId: 1,
            productId: 5,
            quantityRequested: 3.0,
        );

        $line->passQualityCheck(7);

        $this->assertEquals('passed', $line->getQualityCheckStatus());
    }

    public function test_stock_return_line_fail_quality(): void
    {
        $line = new StockReturnLine(
            tenantId: 1,
            stockReturnId: 1,
            productId: 5,
            quantityRequested: 3.0,
        );

        $line->failQualityCheck(7);

        $this->assertEquals('failed', $line->getQualityCheckStatus());
    }

    public function test_stock_return_created_event(): void
    {
        $returnEntity = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $event = new StockReturnCreated($returnEntity);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_stock_return_approved_event(): void
    {
        $returnEntity = new StockReturn(
            tenantId: 1,
            referenceNumber: 'RET-001',
            returnType: 'purchase_return',
            partyId: 10,
            partyType: 'supplier',
        );

        $event = new StockReturnApproved($returnEntity);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_stock_return_not_found_exception(): void
    {
        $exception = new StockReturnNotFoundException(99);
        $message   = $exception->getMessage();

        $this->assertStringContainsString('StockReturn', $message);
        $this->assertStringContainsString('99', $message);
    }

    public function test_stock_return_data_dto(): void
    {
        $this->assertTrue(is_a(StockReturnData::class, BaseDto::class, true));
    }

    public function test_create_return_service_interface(): void
    {
        $this->assertTrue(is_a(CreateStockReturnServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_approve_return_service_interface(): void
    {
        $this->assertTrue(is_a(ApproveStockReturnServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_returns_service_provider_has_register(): void
    {
        $rc = new \ReflectionClass(ReturnsServiceProvider::class);

        $this->assertTrue($rc->hasMethod('register'));
    }

    // ----------------------------------------------------------------
    // GS1 SECTION
    // ----------------------------------------------------------------

    public function test_gs1_identifier_type_values(): void
    {
        $values = Gs1IdentifierType::values();
        $this->assertContains('gtin', $values);
        $this->assertContains('gln', $values);
        $this->assertContains('sscc', $values);
    }

    public function test_gs1_identifier_type_gtin_constant(): void
    {
        $this->assertSame('gtin', Gs1IdentifierType::GTIN);
    }

    public function test_barcode_type_values(): void
    {
        $values = BarcodeType::values();
        $this->assertContains('gs1_128', $values);
        $this->assertContains('ean_13', $values);
        $this->assertContains('qr_code', $values);
    }

    public function test_gs1_identifier_entity_defaults(): void
    {
        $identifier = new Gs1Identifier(
            tenantId: 1,
            identifierType: 'gtin',
            identifierValue: '01234567890128',
        );

        $this->assertTrue($identifier->isActive());
        $this->assertTrue($identifier->isGtin());
        $this->assertFalse($identifier->isGln());
    }

    public function test_gs1_identifier_activate_deactivate(): void
    {
        $identifier = new Gs1Identifier(
            tenantId: 1,
            identifierType: 'gtin',
            identifierValue: '01234567890128',
        );

        $identifier->deactivate();
        $this->assertFalse($identifier->isActive());

        $identifier->activate();
        $this->assertTrue($identifier->isActive());
    }

    public function test_gs1_barcode_entity(): void
    {
        $barcode = new Gs1Barcode(
            tenantId: 1,
            gs1IdentifierId: 1,
            barcodeType: 'ean_13',
            barcodeData: 'data',
        );

        $this->assertFalse($barcode->isPrimary());
    }

    public function test_gs1_barcode_set_primary(): void
    {
        $barcode = new Gs1Barcode(
            tenantId: 1,
            gs1IdentifierId: 1,
            barcodeType: 'ean_13',
            barcodeData: 'data',
        );

        $barcode->setPrimary();

        $this->assertTrue($barcode->isPrimary());
    }

    public function test_gs1_identifier_created_event(): void
    {
        $identifier = new Gs1Identifier(
            tenantId: 1,
            identifierType: 'gtin',
            identifierValue: '01234567890128',
        );

        $event = new Gs1IdentifierCreated($identifier);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_gs1_barcode_created_event(): void
    {
        $barcode = new Gs1Barcode(
            tenantId: 1,
            gs1IdentifierId: 1,
            barcodeType: 'ean_13',
            barcodeData: 'data',
        );

        $event = new Gs1BarcodeCreated($barcode);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_gs1_identifier_not_found_exception(): void
    {
        $exception = new Gs1IdentifierNotFoundException(1);

        $this->assertStringContainsString('Gs1Identifier', $exception->getMessage());
    }

    public function test_gs1_barcode_not_found_exception(): void
    {
        $exception = new Gs1BarcodeNotFoundException(2);

        $this->assertStringContainsString('Gs1Barcode', $exception->getMessage());
    }

    public function test_gs1_identifier_data_dto(): void
    {
        $this->assertTrue(is_a(Gs1IdentifierData::class, BaseDto::class, true));
    }

    public function test_gs1_barcode_data_dto(): void
    {
        $this->assertTrue(is_a(Gs1BarcodeData::class, BaseDto::class, true));
    }

    public function test_create_gs1_identifier_service_interface(): void
    {
        $this->assertTrue(is_a(CreateGs1IdentifierServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_gs1_identifier_service_interface(): void
    {
        $this->assertTrue(is_a(FindGs1IdentifierServiceInterface::class, ReadServiceInterface::class, true));
    }

    public function test_create_gs1_barcode_service_interface(): void
    {
        $this->assertTrue(is_a(CreateGs1BarcodeServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_gs1_identifier_model_table(): void
    {
        $model = new Gs1IdentifierModel;
        $this->assertEquals('gs1_identifiers', $model->getTable());
    }

    public function test_gs1_barcode_model_table(): void
    {
        $model = new Gs1BarcodeModel;
        $this->assertEquals('gs1_barcodes', $model->getTable());
    }

    public function test_gs1_identifier_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(EloquentGs1IdentifierRepository::class, Gs1IdentifierRepositoryInterface::class, true));
    }

    public function test_gs1_barcode_repo_implements_interface(): void
    {
        $this->assertTrue(is_a(EloquentGs1BarcodeRepository::class, Gs1BarcodeRepositoryInterface::class, true));
    }

    public function test_gs1_identifier_controller_injects_find_service(): void
    {
        $rc          = new \ReflectionClass(Gs1IdentifierController::class);
        $constructor = $rc->getConstructor();
        $params      = $constructor->getParameters();
        $types       = array_map(
            fn (\ReflectionParameter $p) => $p->getType() instanceof \ReflectionNamedType
                ? $p->getType()->getName()
                : null,
            $params
        );

        $this->assertContains(FindGs1IdentifierServiceInterface::class, $types);
    }

    public function test_store_gs1_identifier_request_has_rules(): void
    {
        $request = new StoreGs1IdentifierRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('identifier_type', $rules);
    }

    public function test_gs1_identifier_resource_keys(): void
    {
        $identifier = new Gs1Identifier(
            tenantId: 1,
            identifierType: 'gtin',
            identifierValue: '01234567890128',
        );

        $resource = new Gs1IdentifierResource($identifier);
        $array    = $resource->toArray(null);

        $this->assertArrayHasKey('identifier_type', $array);
        $this->assertArrayHasKey('identifier_value', $array);
    }
}
