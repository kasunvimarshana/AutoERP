<?php

declare(strict_types=1);

namespace Modules\Core\Tenancy;

final class TenantFeature
{
    public const CUSTOMER = 'customer';
    public const SUPPLIER = 'supplier';
    public const HR = 'hr';
    public const ITEM = 'item';
    public const WAREHOUSE = 'warehouse';
    public const INVENTORY = 'inventory';
    public const PURCHASE = 'purchase';
    public const VEHICLE = 'vehicle';
    public const VEHICLE_SERVICE = 'vehicle-service';
    public const VEHICLE_RENTAL = 'vehicle-rental';
    public const INVOICE = 'invoice';
    public const PAYMENT = 'payment';
    public const FINANCE = 'finance';
    public const REPORTING = 'reporting';

    private function __construct() {}
}
