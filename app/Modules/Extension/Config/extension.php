<?php

declare(strict_types=1);

use Modules\Customer\Models\Customer;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Hr\Models\HrEmployee;
use Modules\Invoice\Models\Invoice;
use Modules\Item\Models\Item;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\Warehouse\Models\WarehouseModel;

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],

    // Stable public aliases only. Raw class names and database table names are
    // deliberately rejected so callers cannot probe arbitrary models.
    'entity_types' => [
        'customer' => Customer::class,
        'finance_journal_entry' => FinanceJournalEntry::class,
        'goods_receipt_note' => GoodsReceiptNote::class,
        'hr_employee' => HrEmployee::class,
        'invoice' => Invoice::class,
        'item' => Item::class,
        'organization_unit' => OrganizationUnitModel::class,
        'payment' => Payment::class,
        'purchase_order' => PurchaseOrder::class,
        'purchase_return' => PurchaseReturn::class,
        'rental_agreement' => RentalAgreement::class,
        'rental_reservation' => RentalReservation::class,
        'sales_delivery' => SalesDelivery::class,
        'sales_order' => SalesOrder::class,
        'sales_return' => SalesReturn::class,
        'supplier' => Supplier::class,
        'vehicle' => Vehicle::class,
        'vehicle_service_job' => VehicleServiceJob::class,
        'warehouse' => WarehouseModel::class,
    ],
];
