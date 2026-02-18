# POS Module

The Point of Sale (POS) module provides comprehensive retail and restaurant management capabilities for the AutoERP system.

## Features

### Core Features
- **Multi-Location Support**: Manage multiple business locations/stores
- **Cash Register Management**: Open/close cash registers with balance tracking
- **Transaction Processing**: Sales, purchases, returns, and quotations
- **Payment Processing**: Multiple payment methods support
- **Stock Management**: Stock adjustments and transfers between locations

### Product Management
- **Product Variations**: Manage product variants (size, color, etc.)
- **Variation Templates**: Reusable templates for product variations
- **Barcode Management**: Generate and manage product barcodes
- **Product Racks**: Track shelf/rack locations for products
- **Location-based Stock**: Track stock levels per location
- **Selling Price Groups**: Different pricing for customer groups

### Customer Management
- **Customer Groups**: Organize customers into groups
- **Group-based Pricing**: Set different prices for customer groups
- **Discount Management**: Percentage or fixed discounts

### Financial Management
- **Tax Rates**: Flexible tax configuration with groups and sub-taxes
- **Payment Methods**: Configure multiple payment methods
- **Expense Tracking**: Track business expenses by category
- **Invoice Schemes**: Customizable invoice numbering
- **Invoice Layouts**: Customizable invoice templates

### Restaurant Features
- **Table Management**: Manage restaurant tables
- **Table Bookings**: Handle reservations
- **Modifier Sets**: Product modifiers (e.g., toppings, sides)

### Additional Features
- **Barcode Label Printing**: Configurable barcode label printing
- **Printer Configuration**: Configure multiple printers (receipt, label, kitchen)
- **Reference Counters**: Automatic document numbering

## Installation

The POS module is located at `/backend/modules/POS/` and follows the standard AutoERP module structure.

### Register the Service Provider

Add the service provider to `config/app.php`:

```php
'providers' => [
    // Other providers...
    Modules\POS\Providers\POSServiceProvider::class,
],
```

### Run Migrations

```bash
php artisan migrate
```

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=pos-config
```

## Database Schema

### Core Tables
- `pos_business_locations` - Business locations/stores
- `pos_cash_registers` - Cash registers per location
- `pos_cash_register_transactions` - Cash register movements
- `pos_transactions` - Main transaction table
- `pos_transaction_lines` - Transaction line items
- `pos_transaction_payments` - Transaction payment records

### Product & Pricing
- `pos_product_variations` - Product variations
- `pos_variation_templates` - Variation templates
- `pos_variation_location_details` - Stock by location
- `pos_selling_price_groups` - Pricing groups
- `pos_customer_groups` - Customer grouping
- `pos_product_racks` - Product shelf locations

### Financial
- `pos_payment_methods` - Payment methods configuration
- `pos_tax_rates` - Tax rates and groups
- `pos_invoice_schemes` - Invoice numbering schemes
- `pos_invoice_layouts` - Invoice layout templates

### Stock Management
- `pos_stock_adjustments` - Stock adjustments
- `pos_stock_adjustment_lines` - Adjustment line items

### Expenses
- `pos_expense_categories` - Expense categories
- `pos_expenses` - Expense records

### Restaurant
- `pos_restaurant_tables` - Restaurant tables
- `pos_restaurant_bookings` - Table bookings
- `pos_modifier_sets` - Product modifiers

### Configuration
- `pos_printer_config` - Printer configuration
- `pos_barcode_config` - Barcode label configuration
- `pos_reference_counters` - Document number counters

## API Endpoints

### Business Locations
- `GET /api/pos/locations` - List all locations
- `POST /api/pos/locations` - Create a location
- `GET /api/pos/locations/active` - Get active locations
- `GET /api/pos/locations/{id}` - Get location details
- `PUT /api/pos/locations/{id}` - Update location
- `DELETE /api/pos/locations/{id}` - Delete location

### Cash Registers
- `GET /api/pos/cash-registers` - List all registers
- `POST /api/pos/cash-registers` - Create a register
- `GET /api/pos/cash-registers/{id}` - Get register details
- `POST /api/pos/cash-registers/{id}/open` - Open register
- `POST /api/pos/cash-registers/{id}/close` - Close register
- `GET /api/pos/cash-registers/{id}/balance` - Get current balance

### Transactions
- `GET /api/pos/transactions` - List transactions (with filters)
- `POST /api/pos/transactions` - Create transaction
- `GET /api/pos/transactions/{id}` - Get transaction details
- `PUT /api/pos/transactions/{id}` - Update transaction
- `DELETE /api/pos/transactions/{id}` - Delete transaction
- `POST /api/pos/transactions/{id}/complete` - Complete transaction
- `POST /api/pos/transactions/{id}/cancel` - Cancel transaction
- `POST /api/pos/transactions/{id}/payments` - Add payment

## Usage Examples

### Creating a Business Location

```php
use Modules\POS\Services\BusinessLocationService;

$locationService = app(BusinessLocationService::class);

$location = $locationService->create([
    'name' => 'Main Store',
    'code' => 'MAIN',
    'address' => '123 Main Street',
    'city' => 'New York',
    'country' => 'USA',
    'phone' => '+1234567890',
    'is_active' => true,
]);
```

### Opening a Cash Register

```php
use Modules\POS\Services\CashRegisterService;
use Modules\POS\Models\CashRegister;

$cashRegisterService = app(CashRegisterService::class);

$register = CashRegister::find($registerId);
$opened = $cashRegisterService->openRegister(
    $register,
    100.00, // opening balance
    auth()->id()
);
```

### Creating a Transaction

```php
use Modules\POS\Services\TransactionService;

$transactionService = app(TransactionService::class);

$transaction = $transactionService->createTransaction([
    'location_id' => $locationId,
    'type' => 'sale',
    'status' => 'completed',
    'contact_id' => $customerId,
    'cash_register_id' => $registerId,
    'transaction_date' => now(),
    'subtotal' => 100.00,
    'tax_amount' => 10.00,
    'total_amount' => 110.00,
    'lines' => [
        [
            'product_id' => $productId,
            'variation_id' => $variationId,
            'quantity' => 2,
            'unit_price' => 50.00,
            'line_total' => 100.00,
        ],
    ],
    'payments' => [
        [
            'payment_method_id' => $paymentMethodId,
            'amount' => 110.00,
        ],
    ],
]);
```

### Creating a Stock Adjustment

```php
use Modules\POS\Services\StockAdjustmentService;

$stockAdjustmentService = app(StockAdjustmentService::class);

$adjustment = $stockAdjustmentService->createAdjustment([
    'location_id' => $locationId,
    'adjustment_date' => now(),
    'type' => 'normal',
    'total_amount' => 500.00,
    'reason' => 'Monthly stock take',
    'lines' => [
        [
            'product_id' => $productId,
            'variation_id' => $variationId,
            'quantity' => 10,
            'unit_cost' => 50.00,
            'line_total' => 500.00,
        ],
    ],
]);
```

## Models

All models extend `Modules\Core\Models\BaseModel` and use the `BelongsToTenant` trait for multi-tenancy support.

### Key Models
- `BusinessLocation` - Business location/store
- `CashRegister` - Cash register
- `Transaction` - POS transaction
- `TransactionLine` - Transaction line item
- `TransactionPayment` - Payment record
- `PaymentMethod` - Payment method configuration
- `TaxRate` - Tax rate configuration
- `ProductVariation` - Product variation
- `StockAdjustment` - Stock adjustment
- `Expense` - Expense record

## Enums

- `TransactionType` - Transaction types (sale, purchase, return, etc.)
- `TransactionStatus` - Transaction statuses (draft, completed, cancelled, etc.)
- `PaymentStatus` - Payment statuses (paid, partial, due, pending)
- `PaymentMethod` - Payment methods (cash, card, etc.)
- `AccountingMethod` - Accounting methods (FIFO, LIFO, Average)
- `CashRegisterStatus` - Cash register statuses (open, closed)
- `TaxCalculationType` - Tax calculation types (inclusive, exclusive)
- `BookingStatus` - Restaurant booking statuses

## Services

- `TransactionService` - Handle transaction operations
- `CashRegisterService` - Manage cash registers
- `StockAdjustmentService` - Handle stock adjustments
- `BusinessLocationService` - Manage business locations
- `ReferenceNumberService` - Generate reference numbers

## Events

(To be implemented based on business requirements)

## Permissions

The module provides the following permissions:
- `pos.transactions.*` - Transaction management
- `pos.locations.*` - Location management
- `pos.cash-registers.*` - Cash register operations
- `pos.stock-adjustments.*` - Stock adjustment management
- `pos.expenses.*` - Expense management
- `pos.settings.manage` - Settings management

## Configuration

Configuration file: `config/pos.php`

Key configuration options:
- `default_location` - Default business location
- `cash_register.auto_open` - Auto-open cash register
- `transaction.default_prefix` - Transaction number prefix
- `payment.allow_partial` - Allow partial payments
- `stock.auto_adjust` - Auto-adjust stock on transactions
- `restaurant.enabled` - Enable restaurant features

## Integration

### With Inventory Module
The POS module integrates with the Inventory module for:
- Product information
- Stock level management
- Product variations

### With Accounting Module
Future integration will include:
- Journal entry generation
- Account linkage for payment methods
- Revenue and expense recording

### With Sales Module
The POS module can work alongside the Sales module:
- POS for retail/counter sales
- Sales module for order management

## License

This module is part of the AutoERP system and follows the same license.
