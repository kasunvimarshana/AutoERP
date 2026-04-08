<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// Pricing
use Modules\Pricing\Domain\ValueObjects\PriceListType;
use Modules\Pricing\Domain\ValueObjects\PricingMethod;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Events\PriceListCreated;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Services\CreatePriceListService;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListController;
use Modules\Pricing\Infrastructure\Http\Requests\StorePriceListRequest;
use Modules\Pricing\Infrastructure\Http\Resources\PriceListResource;
use Modules\Pricing\Infrastructure\Providers\PricingServiceProvider;

// Taxation
use Modules\Taxation\Domain\ValueObjects\TaxType;
use Modules\Taxation\Domain\ValueObjects\TaxCalculationMethod;
use Modules\Taxation\Domain\Entities\TaxRate;
use Modules\Taxation\Domain\Entities\TaxRule;
use Modules\Taxation\Domain\Events\TaxRateCreated;
use Modules\Taxation\Domain\Exceptions\TaxRateNotFoundException;
use Modules\Taxation\Application\DTOs\TaxRateData;
use Modules\Taxation\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Taxation\Application\Services\CreateTaxRateService;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\Taxation\Infrastructure\Http\Controllers\TaxRateController;
use Modules\Taxation\Infrastructure\Providers\TaxationServiceProvider;

// Transaction
use Modules\Transaction\Domain\ValueObjects\TransactionType;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Entities\JournalEntry;
use Modules\Transaction\Domain\Events\TransactionCreated;
use Modules\Transaction\Domain\Exceptions\TransactionNotFoundException;
use Modules\Transaction\Application\DTOs\TransactionData;
use Modules\Transaction\Application\Contracts\CreateTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\PostTransactionServiceInterface;
use Modules\Transaction\Application\Services\CreateTransactionService;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\TransactionModel;
use Modules\Transaction\Infrastructure\Http\Controllers\TransactionController;
use Modules\Transaction\Infrastructure\Providers\TransactionServiceProvider;

// Settings
use Modules\Settings\Domain\ValueObjects\SettingType;
use Modules\Settings\Domain\Entities\Setting;
use Modules\Settings\Domain\Events\SettingCreated;
use Modules\Settings\Domain\Exceptions\SettingNotFoundException;
use Modules\Settings\Application\DTOs\SettingData;
use Modules\Settings\Application\Contracts\CreateSettingServiceInterface;
use Modules\Settings\Application\Services\CreateSettingService;
use Modules\Settings\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Modules\Settings\Infrastructure\Http\Controllers\SettingController;
use Modules\Settings\Infrastructure\Providers\SettingsServiceProvider;

// Core base classes
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\Contracts\ReadServiceInterface;

class PricingTaxationTransactionSettingsModuleTest extends TestCase
{
    // ----------------------------------------------------------------
    // PRICING MODULE
    // ----------------------------------------------------------------

    public function test_price_list_type_constants(): void
    {
        $this->assertSame('sale', PriceListType::SALE);
        $this->assertSame('purchase', PriceListType::PURCHASE);
        $this->assertSame('special', PriceListType::SPECIAL);
        $this->assertSame('promotional', PriceListType::PROMOTIONAL);
        $this->assertSame('customer_specific', PriceListType::CUSTOMER_SPECIFIC);
        $this->assertSame('wholesale', PriceListType::WHOLESALE);
        $this->assertSame('retail', PriceListType::RETAIL);
    }

    public function test_price_list_type_values(): void
    {
        $values = PriceListType::values();
        $this->assertContains('sale', $values);
        $this->assertContains('purchase', $values);
        $this->assertContains('special', $values);
        $this->assertContains('promotional', $values);
        $this->assertContains('customer_specific', $values);
        $this->assertContains('wholesale', $values);
        $this->assertContains('retail', $values);
    }

    public function test_pricing_method_constants(): void
    {
        $this->assertSame('fixed', PricingMethod::FIXED);
        $this->assertSame('percentage_discount', PricingMethod::PERCENTAGE_DISCOUNT);
        $this->assertSame('percentage_markup', PricingMethod::PERCENTAGE_MARKUP);
        $this->assertSame('formula', PricingMethod::FORMULA);
    }

    public function test_pricing_method_values(): void
    {
        $values = PricingMethod::values();
        $this->assertContains('fixed', $values);
        $this->assertContains('percentage_discount', $values);
        $this->assertContains('percentage_markup', $values);
        $this->assertContains('formula', $values);
    }

    public function test_price_list_entity_construction(): void
    {
        $priceList = new PriceList(
            tenantId: 1,
            name: 'Standard Prices',
            code: 'STD-001',
            type: 'sale',
        );

        $this->assertEquals(1, $priceList->getTenantId());
        $this->assertEquals('Standard Prices', $priceList->getName());
        $this->assertEquals('STD-001', $priceList->getCode());
        $this->assertEquals('sale', $priceList->getType());
    }

    public function test_price_list_entity_defaults(): void
    {
        $priceList = new PriceList(
            tenantId: 1,
            name: 'Standard Prices',
            code: 'STD-001',
            type: 'sale',
        );

        $this->assertEquals('fixed', $priceList->getPricingMethod());
        $this->assertEquals('USD', $priceList->getCurrencyCode());
        $this->assertTrue($priceList->isActive());
        $this->assertNull($priceList->getId());
    }

    public function test_price_list_activate_deactivate(): void
    {
        $priceList = new PriceList(
            tenantId: 1,
            name: 'Standard Prices',
            code: 'STD-001',
            type: 'sale',
            isActive: false,
        );

        $this->assertFalse($priceList->isActive());

        $priceList->activate();
        $this->assertTrue($priceList->isActive());

        $priceList->deactivate();
        $this->assertFalse($priceList->isActive());
    }

    public function test_price_list_is_expired(): void
    {
        $pastDate = new \DateTimeImmutable('-1 day');
        $priceList = new PriceList(
            tenantId: 1,
            name: 'Expired List',
            code: 'EXP-001',
            type: 'sale',
            endDate: $pastDate,
        );

        $this->assertTrue($priceList->isExpired());
    }

    public function test_price_list_is_valid_when_active_no_dates(): void
    {
        $priceList = new PriceList(
            tenantId: 1,
            name: 'Valid List',
            code: 'VLD-001',
            type: 'sale',
        );

        $this->assertTrue($priceList->isValid());
    }

    public function test_price_list_created_event(): void
    {
        $priceList = new PriceList(
            tenantId: 1,
            name: 'Standard Prices',
            code: 'STD-001',
            type: 'sale',
        );

        $event = new PriceListCreated($priceList);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_price_list_created_event_broadcast_with(): void
    {
        $priceList = new PriceList(
            tenantId: 5,
            name: 'Standard Prices',
            code: 'STD-001',
            type: 'sale',
        );

        $event = new PriceListCreated($priceList);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertEquals(5, $payload['tenantId']);
    }

    public function test_price_list_not_found_exception(): void
    {
        $exception = new PriceListNotFoundException(99);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertStringContainsString('PriceList', $exception->getMessage());
        $this->assertStringContainsString('99', $exception->getMessage());
    }

    public function test_price_list_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(is_a(PriceListData::class, BaseDto::class, true));
    }

    public function test_price_list_data_from_array(): void
    {
        $dto = PriceListData::fromArray([
            'tenantId' => 1,
            'name'     => 'Test List',
            'code'     => 'TEST-001',
            'type'     => 'sale',
        ]);

        $this->assertEquals(1, $dto->tenantId);
        $this->assertEquals('Test List', $dto->name);
        $this->assertEquals('TEST-001', $dto->code);
        $this->assertEquals('sale', $dto->type);
    }

    public function test_price_list_data_to_array(): void
    {
        $dto = PriceListData::fromArray([
            'tenantId' => 1,
            'name'     => 'Test List',
            'code'     => 'TEST-001',
            'type'     => 'sale',
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenantId', $array);
        $this->assertArrayHasKey('name', $array);
    }

    public function test_price_list_data_rules(): void
    {
        $dto = new PriceListData();
        $rules = $dto->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('tenantId', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('type', $rules);
    }

    public function test_create_price_list_service_interface_extends_write_service(): void
    {
        $this->assertTrue(is_a(CreatePriceListServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_find_price_list_service_interface_extends_read_service(): void
    {
        $this->assertTrue(is_a(FindPriceListServiceInterface::class, ReadServiceInterface::class, true));
    }

    public function test_create_price_list_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreatePriceListService::class));
    }

    public function test_price_list_model_table_name(): void
    {
        $model = new PriceListModel();
        $this->assertEquals('price_lists', $model->getTable());
    }

    public function test_eloquent_price_list_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentPriceListRepository::class));
    }

    public function test_price_list_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(PriceListController::class));
    }

    public function test_store_price_list_request_rules(): void
    {
        $request = new StorePriceListRequest();
        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('type', $rules);
    }

    public function test_price_list_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(PriceListResource::class));
    }

    public function test_pricing_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(PricingServiceProvider::class));
    }

    // ----------------------------------------------------------------
    // TAXATION MODULE
    // ----------------------------------------------------------------

    public function test_tax_type_constants(): void
    {
        $this->assertSame('vat', TaxType::VAT);
        $this->assertSame('gst', TaxType::GST);
        $this->assertSame('sales_tax', TaxType::SALES_TAX);
        $this->assertSame('excise', TaxType::EXCISE);
        $this->assertSame('customs', TaxType::CUSTOMS);
        $this->assertSame('withholding', TaxType::WITHHOLDING);
        $this->assertSame('service_tax', TaxType::SERVICE_TAX);
        $this->assertSame('income_tax', TaxType::INCOME_TAX);
    }

    public function test_tax_type_values(): void
    {
        $values = TaxType::values();
        $this->assertContains('vat', $values);
        $this->assertContains('gst', $values);
        $this->assertContains('sales_tax', $values);
        $this->assertContains('excise', $values);
        $this->assertContains('income_tax', $values);
    }

    public function test_tax_calculation_method_constants(): void
    {
        $this->assertSame('inclusive', TaxCalculationMethod::INCLUSIVE);
        $this->assertSame('exclusive', TaxCalculationMethod::EXCLUSIVE);
        $this->assertSame('compound', TaxCalculationMethod::COMPOUND);
    }

    public function test_tax_calculation_method_values(): void
    {
        $values = TaxCalculationMethod::values();
        $this->assertContains('inclusive', $values);
        $this->assertContains('exclusive', $values);
        $this->assertContains('compound', $values);
    }

    public function test_tax_rate_entity_construction(): void
    {
        $taxRate = new TaxRate(
            tenantId: 1,
            name: 'Standard VAT',
            code: 'VAT-20',
            taxType: 'vat',
            rate: 20.0,
        );

        $this->assertEquals(1, $taxRate->getTenantId());
        $this->assertEquals('Standard VAT', $taxRate->getName());
        $this->assertEquals('VAT-20', $taxRate->getCode());
        $this->assertEquals('vat', $taxRate->getTaxType());
        $this->assertEquals(20.0, $taxRate->getRate());
    }

    public function test_tax_rate_entity_defaults(): void
    {
        $taxRate = new TaxRate(
            tenantId: 1,
            name: 'Standard VAT',
            code: 'VAT-20',
            taxType: 'vat',
            rate: 20.0,
        );

        $this->assertEquals('exclusive', $taxRate->getCalculationMethod());
        $this->assertTrue($taxRate->isActive());
        $this->assertNull($taxRate->getId());
    }

    public function test_tax_rate_activate_deactivate(): void
    {
        $taxRate = new TaxRate(
            tenantId: 1,
            name: 'Standard VAT',
            code: 'VAT-20',
            taxType: 'vat',
            rate: 20.0,
            isActive: false,
        );

        $this->assertFalse($taxRate->isActive());

        $taxRate->activate();
        $this->assertTrue($taxRate->isActive());

        $taxRate->deactivate();
        $this->assertFalse($taxRate->isActive());
    }

    public function test_tax_rate_is_effective_when_active(): void
    {
        $taxRate = new TaxRate(
            tenantId: 1,
            name: 'Standard VAT',
            code: 'VAT-20',
            taxType: 'vat',
            rate: 20.0,
        );

        $this->assertTrue($taxRate->isEffective());
    }

    public function test_tax_rule_entity_construction(): void
    {
        $taxRule = new TaxRule(
            tenantId: 1,
            name: 'Default VAT Rule',
            taxRateId: 5,
            entityType: 'product',
        );

        $this->assertEquals(1, $taxRule->getTenantId());
        $this->assertEquals('Default VAT Rule', $taxRule->getName());
        $this->assertEquals(5, $taxRule->getTaxRateId());
        $this->assertEquals('product', $taxRule->getEntityType());
    }

    public function test_tax_rule_entity_defaults(): void
    {
        $taxRule = new TaxRule(
            tenantId: 1,
            name: 'Default VAT Rule',
            taxRateId: 5,
            entityType: 'product',
        );

        $this->assertEquals(0, $taxRule->getPriority());
        $this->assertTrue($taxRule->isActive());
        $this->assertNull($taxRule->getId());
    }

    public function test_tax_rule_activate_deactivate(): void
    {
        $taxRule = new TaxRule(
            tenantId: 1,
            name: 'Default VAT Rule',
            taxRateId: 5,
            entityType: 'product',
            isActive: false,
        );

        $taxRule->activate();
        $this->assertTrue($taxRule->isActive());

        $taxRule->deactivate();
        $this->assertFalse($taxRule->isActive());
    }

    public function test_tax_rate_created_event(): void
    {
        $taxRate = new TaxRate(
            tenantId: 1,
            name: 'Standard VAT',
            code: 'VAT-20',
            taxType: 'vat',
            rate: 20.0,
        );

        $event = new TaxRateCreated($taxRate);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_tax_rate_created_event_broadcast_with(): void
    {
        $taxRate = new TaxRate(
            tenantId: 3,
            name: 'Standard VAT',
            code: 'VAT-20',
            taxType: 'vat',
            rate: 20.0,
        );

        $event = new TaxRateCreated($taxRate);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertEquals(3, $payload['tenantId']);
    }

    public function test_tax_rate_not_found_exception(): void
    {
        $exception = new TaxRateNotFoundException(42);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertStringContainsString('TaxRate', $exception->getMessage());
        $this->assertStringContainsString('42', $exception->getMessage());
    }

    public function test_tax_rate_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(is_a(TaxRateData::class, BaseDto::class, true));
    }

    public function test_tax_rate_data_from_array(): void
    {
        $dto = TaxRateData::fromArray([
            'tenantId' => 1,
            'name'     => 'Standard VAT',
            'code'     => 'VAT-20',
            'taxType'  => 'vat',
            'rate'     => 20.0,
        ]);

        $this->assertEquals(1, $dto->tenantId);
        $this->assertEquals('Standard VAT', $dto->name);
        $this->assertEquals('vat', $dto->taxType);
        $this->assertEquals(20.0, $dto->rate);
    }

    public function test_tax_rate_data_to_array(): void
    {
        $dto = TaxRateData::fromArray([
            'tenantId' => 1,
            'name'     => 'Standard VAT',
            'code'     => 'VAT-20',
            'taxType'  => 'vat',
            'rate'     => 20.0,
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenantId', $array);
        $this->assertArrayHasKey('taxType', $array);
    }

    public function test_tax_rate_data_rules(): void
    {
        $dto = new TaxRateData();
        $rules = $dto->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('tenantId', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('taxType', $rules);
        $this->assertArrayHasKey('rate', $rules);
    }

    public function test_create_tax_rate_service_interface_extends_write_service(): void
    {
        $this->assertTrue(is_a(CreateTaxRateServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_create_tax_rate_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateTaxRateService::class));
    }

    public function test_tax_rate_model_table_name(): void
    {
        $model = new TaxRateModel();
        $this->assertEquals('tax_rates', $model->getTable());
    }

    public function test_tax_rate_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TaxRateController::class));
    }

    public function test_taxation_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(TaxationServiceProvider::class));
    }

    // ----------------------------------------------------------------
    // TRANSACTION MODULE
    // ----------------------------------------------------------------

    public function test_transaction_type_constants(): void
    {
        $this->assertSame('payment', TransactionType::PAYMENT);
        $this->assertSame('receipt', TransactionType::RECEIPT);
        $this->assertSame('transfer', TransactionType::TRANSFER);
        $this->assertSame('adjustment', TransactionType::ADJUSTMENT);
        $this->assertSame('refund', TransactionType::REFUND);
        $this->assertSame('journal', TransactionType::JOURNAL);
        $this->assertSame('opening_balance', TransactionType::OPENING_BALANCE);
    }

    public function test_transaction_type_values(): void
    {
        $values = TransactionType::values();
        $this->assertContains('payment', $values);
        $this->assertContains('receipt', $values);
        $this->assertContains('transfer', $values);
        $this->assertContains('journal', $values);
        $this->assertContains('opening_balance', $values);
    }

    public function test_transaction_status_constants(): void
    {
        $this->assertSame('draft', TransactionStatus::DRAFT);
        $this->assertSame('pending', TransactionStatus::PENDING);
        $this->assertSame('posted', TransactionStatus::POSTED);
        $this->assertSame('voided', TransactionStatus::VOIDED);
        $this->assertSame('reversed', TransactionStatus::REVERSED);
    }

    public function test_transaction_status_values(): void
    {
        $values = TransactionStatus::values();
        $this->assertContains('draft', $values);
        $this->assertContains('posted', $values);
        $this->assertContains('voided', $values);
    }

    public function test_transaction_entity_construction(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $transaction = new Transaction(
            tenantId: 1,
            referenceNumber: 'TXN-001',
            transactionType: 'payment',
            amount: 1000.0,
            transactionDate: $date,
        );

        $this->assertEquals(1, $transaction->getTenantId());
        $this->assertEquals('TXN-001', $transaction->getReferenceNumber());
        $this->assertEquals('payment', $transaction->getTransactionType());
        $this->assertEquals(1000.0, $transaction->getAmount());
    }

    public function test_transaction_entity_defaults(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $transaction = new Transaction(
            tenantId: 1,
            referenceNumber: 'TXN-001',
            transactionType: 'payment',
            amount: 1000.0,
            transactionDate: $date,
        );

        $this->assertEquals('draft', $transaction->getStatus());
        $this->assertTrue($transaction->isDraft());
        $this->assertEquals('USD', $transaction->getCurrencyCode());
        $this->assertEquals(1.0, $transaction->getExchangeRate());
    }

    public function test_transaction_post(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $transaction = new Transaction(
            tenantId: 1,
            referenceNumber: 'TXN-001',
            transactionType: 'payment',
            amount: 1000.0,
            transactionDate: $date,
        );

        $transaction->post();

        $this->assertEquals('posted', $transaction->getStatus());
        $this->assertTrue($transaction->isPosted());
        $this->assertNotNull($transaction->getPostedAt());
    }

    public function test_transaction_void(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $transaction = new Transaction(
            tenantId: 1,
            referenceNumber: 'TXN-001',
            transactionType: 'payment',
            amount: 1000.0,
            transactionDate: $date,
        );

        $transaction->void('Error in amount');

        $this->assertEquals('voided', $transaction->getStatus());
        $this->assertTrue($transaction->isVoided());
        $this->assertEquals('Error in amount', $transaction->getVoidReason());
    }

    public function test_journal_entry_entity_construction(): void
    {
        $entry = new JournalEntry(
            tenantId: 1,
            transactionId: 10,
            accountCode: 'ACC-001',
            accountName: 'Cash Account',
            debitAmount: 500.0,
            creditAmount: 0.0,
        );

        $this->assertEquals(1, $entry->getTenantId());
        $this->assertEquals(10, $entry->getTransactionId());
        $this->assertEquals('ACC-001', $entry->getAccountCode());
        $this->assertEquals('Cash Account', $entry->getAccountName());
        $this->assertEquals(500.0, $entry->getDebitAmount());
    }

    public function test_journal_entry_get_net_amount(): void
    {
        $entry = new JournalEntry(
            tenantId: 1,
            transactionId: 10,
            accountCode: 'ACC-001',
            accountName: 'Cash Account',
            debitAmount: 500.0,
            creditAmount: 200.0,
        );

        $this->assertEquals(300.0, $entry->getNetAmount());
    }

    public function test_journal_entry_is_debit(): void
    {
        $entry = new JournalEntry(
            tenantId: 1,
            transactionId: 10,
            accountCode: 'ACC-001',
            accountName: 'Cash Account',
            debitAmount: 500.0,
            creditAmount: 100.0,
        );

        $this->assertTrue($entry->isDebit());
    }

    public function test_journal_entry_post(): void
    {
        $entry = new JournalEntry(
            tenantId: 1,
            transactionId: 10,
            accountCode: 'ACC-001',
            accountName: 'Cash Account',
        );

        $this->assertEquals('draft', $entry->getStatus());

        $entry->post();

        $this->assertEquals('posted', $entry->getStatus());
        $this->assertNotNull($entry->getPostedAt());
    }

    public function test_transaction_created_event(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $transaction = new Transaction(
            tenantId: 1,
            referenceNumber: 'TXN-001',
            transactionType: 'payment',
            amount: 1000.0,
            transactionDate: $date,
        );

        $event = new TransactionCreated($transaction);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_transaction_created_event_broadcast_with(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $transaction = new Transaction(
            tenantId: 7,
            referenceNumber: 'TXN-001',
            transactionType: 'payment',
            amount: 1000.0,
            transactionDate: $date,
        );

        $event = new TransactionCreated($transaction);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertEquals(7, $payload['tenantId']);
        $this->assertArrayHasKey('reference_number', $payload);
    }

    public function test_transaction_not_found_exception(): void
    {
        $exception = new TransactionNotFoundException(55);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertStringContainsString('Transaction', $exception->getMessage());
        $this->assertStringContainsString('55', $exception->getMessage());
    }

    public function test_transaction_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(is_a(TransactionData::class, BaseDto::class, true));
    }

    public function test_transaction_data_from_array(): void
    {
        $dto = TransactionData::fromArray([
            'tenantId'        => 1,
            'referenceNumber' => 'TXN-001',
            'transactionType' => 'payment',
            'amount'          => 1000.0,
            'transactionDate' => '2024-01-01',
        ]);

        $this->assertEquals(1, $dto->tenantId);
        $this->assertEquals('TXN-001', $dto->referenceNumber);
        $this->assertEquals('payment', $dto->transactionType);
        $this->assertEquals(1000.0, $dto->amount);
    }

    public function test_transaction_data_to_array(): void
    {
        $dto = TransactionData::fromArray([
            'tenantId'        => 1,
            'referenceNumber' => 'TXN-001',
            'transactionType' => 'payment',
            'amount'          => 1000.0,
            'transactionDate' => '2024-01-01',
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenantId', $array);
        $this->assertArrayHasKey('transactionType', $array);
    }

    public function test_transaction_data_rules(): void
    {
        $dto = new TransactionData();
        $rules = $dto->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('tenantId', $rules);
        $this->assertArrayHasKey('referenceNumber', $rules);
        $this->assertArrayHasKey('transactionType', $rules);
        $this->assertArrayHasKey('amount', $rules);
    }

    public function test_create_transaction_service_interface_extends_write_service(): void
    {
        $this->assertTrue(is_a(CreateTransactionServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_post_transaction_service_interface_extends_write_service(): void
    {
        $this->assertTrue(is_a(PostTransactionServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_create_transaction_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateTransactionService::class));
    }

    public function test_transaction_model_table_name(): void
    {
        $model = new TransactionModel();
        $this->assertEquals('transactions', $model->getTable());
    }

    public function test_transaction_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TransactionController::class));
    }

    public function test_transaction_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(TransactionServiceProvider::class));
    }

    // ----------------------------------------------------------------
    // SETTINGS MODULE
    // ----------------------------------------------------------------

    public function test_setting_type_constants(): void
    {
        $this->assertSame('string', SettingType::STRING);
        $this->assertSame('integer', SettingType::INTEGER);
        $this->assertSame('float', SettingType::FLOAT);
        $this->assertSame('boolean', SettingType::BOOLEAN);
        $this->assertSame('json', SettingType::JSON);
        $this->assertSame('array', SettingType::ARRAY);
    }

    public function test_setting_type_values(): void
    {
        $values = SettingType::values();
        $this->assertContains('string', $values);
        $this->assertContains('integer', $values);
        $this->assertContains('float', $values);
        $this->assertContains('boolean', $values);
        $this->assertContains('json', $values);
        $this->assertContains('array', $values);
    }

    public function test_setting_entity_construction(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'site_name',
            label: 'Site Name',
            value: 'My ERP',
        );

        $this->assertEquals(1, $setting->getTenantId());
        $this->assertEquals('general', $setting->getGroupKey());
        $this->assertEquals('site_name', $setting->getSettingKey());
        $this->assertEquals('Site Name', $setting->getLabel());
    }

    public function test_setting_entity_defaults(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'site_name',
            label: 'Site Name',
        );

        $this->assertEquals('string', $setting->getSettingType());
        $this->assertFalse($setting->isSystem());
        $this->assertTrue($setting->isEditable());
        $this->assertNull($setting->getId());
    }

    public function test_setting_set_value(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'site_name',
            label: 'Site Name',
            value: 'Old Value',
        );

        $setting->setValue('New Value');

        $this->assertEquals('New Value', $setting->getRawValue());
    }

    public function test_setting_is_boolean(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'maintenance_mode',
            label: 'Maintenance Mode',
            settingType: SettingType::BOOLEAN,
        );

        $this->assertTrue($setting->isBoolean());
    }

    public function test_setting_is_json(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'config_data',
            label: 'Config Data',
            settingType: SettingType::JSON,
        );

        $this->assertTrue($setting->isJson());
    }

    public function test_setting_get_cast_value_integer(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'max_items',
            label: 'Max Items',
            value: '42',
            settingType: SettingType::INTEGER,
        );

        $this->assertSame(42, $setting->getCastValue());
    }

    public function test_setting_get_cast_value_boolean(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'enabled',
            label: 'Enabled',
            value: '1',
            settingType: SettingType::BOOLEAN,
        );

        $this->assertTrue($setting->getCastValue());
    }

    public function test_setting_created_event(): void
    {
        $setting = new Setting(
            tenantId: 1,
            groupKey: 'general',
            settingKey: 'site_name',
            label: 'Site Name',
        );

        $event = new SettingCreated($setting);

        $this->assertInstanceOf(BaseEvent::class, $event);
    }

    public function test_setting_created_event_broadcast_with(): void
    {
        $setting = new Setting(
            tenantId: 2,
            groupKey: 'general',
            settingKey: 'site_name',
            label: 'Site Name',
        );

        $event = new SettingCreated($setting);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertEquals(2, $payload['tenantId']);
    }

    public function test_setting_not_found_exception(): void
    {
        $exception = new SettingNotFoundException(77);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertStringContainsString('Setting', $exception->getMessage());
        $this->assertStringContainsString('77', $exception->getMessage());
    }

    public function test_setting_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(is_a(SettingData::class, BaseDto::class, true));
    }

    public function test_setting_data_from_array(): void
    {
        $dto = SettingData::fromArray([
            'tenantId'   => 1,
            'groupKey'   => 'general',
            'settingKey' => 'site_name',
            'label'      => 'Site Name',
            'value'      => 'My ERP',
        ]);

        $this->assertEquals(1, $dto->tenantId);
        $this->assertEquals('general', $dto->groupKey);
        $this->assertEquals('site_name', $dto->settingKey);
        $this->assertEquals('Site Name', $dto->label);
    }

    public function test_setting_data_to_array(): void
    {
        $dto = SettingData::fromArray([
            'tenantId'   => 1,
            'groupKey'   => 'general',
            'settingKey' => 'site_name',
            'label'      => 'Site Name',
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenantId', $array);
        $this->assertArrayHasKey('groupKey', $array);
    }

    public function test_setting_data_rules(): void
    {
        $dto = new SettingData();
        $rules = $dto->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('tenantId', $rules);
        $this->assertArrayHasKey('groupKey', $rules);
        $this->assertArrayHasKey('settingKey', $rules);
        $this->assertArrayHasKey('label', $rules);
        $this->assertArrayHasKey('settingType', $rules);
    }

    public function test_create_setting_service_interface_extends_write_service(): void
    {
        $this->assertTrue(is_a(CreateSettingServiceInterface::class, WriteServiceInterface::class, true));
    }

    public function test_create_setting_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateSettingService::class));
    }

    public function test_setting_model_table_name(): void
    {
        $model = new SettingModel();
        $this->assertEquals('settings', $model->getTable());
    }

    public function test_setting_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SettingController::class));
    }

    public function test_settings_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(SettingsServiceProvider::class));
    }
}
