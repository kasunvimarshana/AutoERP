<?php

use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemBrandController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\UOMController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware([])->group(function () {
    // System User
    Route::post('system-users', [SystemUserController::class, 'store']);
    Route::get('system-users', [SystemUserController::class, 'index']);
    Route::get('system-users/{system_user}', [SystemUserController::class, 'show']);
    Route::put('system-users/{system_user}', [SystemUserController::class, 'update']);
    Route::delete('system-users/{system_user}', [SystemUserController::class, 'delete']);

    // Supplier
    Route::post('suppliers', [SupplierController::class, 'store']);
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'delete']);
    Route::post('suppliers/{supplier}', [SupplierController::class, 'delete']);
    Route::post('suppliers/vehicles', [SupplierController::class, 'assignVehicle']);
    Route::delete('suppliers/vehicles/{supplier_vehicle}', [SupplierController::class, 'removeVehicle']);
    Route::get('suppliers/vehicles/{supplier}', [SupplierController::class, 'vehicles']);

    // Customer
    Route::post('customers', [CustomerController::class, 'store']);
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
    Route::put('customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('customers/{customer}', [CustomerController::class, 'delete']);
    Route::post('customers/vehicles', [CustomerController::class, 'assignVehicle']);
    Route::delete('customers/vehicles/{customer_vehicle}', [CustomerController::class, 'removeVehicle']);
    Route::get('customers/vehicles/{customer}', [CustomerController::class, 'vehicles']);

    // Vehicle
    Route::post('vehicles', [VehicleController::class, 'store']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
    Route::delete('vehicles/{vehicle}', [VehicleController::class, 'delete']);

    // Department
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);
    Route::put('departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('departments/{department}', [DepartmentController::class, 'delete']);

    // Designation
    Route::post('designations', [DesignationController::class, 'store']);
    Route::get('designations', [VehicleController::class, 'index']);
    Route::get('designations/{designation}', [VehicleController::class, 'show']);
    Route::put('designations/{designation}', [VehicleController::class, 'update']);
    Route::delete('designations/{designation}', [VehicleController::class, 'delete']);

    // Employment Type
    Route::post('vehicles', [EmploymentTypeController::class, 'store']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
    Route::delete('vehicles/{vehicle}', [VehicleController::class, 'delete']);

    // Employee
    Route::post('employees', [EmployeeController::class, 'store']);
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::get('employees/{employee}', [EmployeeController::class, 'show']);
    Route::put('employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('employees/{employee}', [EmployeeController::class, 'delete']);

    // Warehouse
    Route::post('warehouses', [WarehouseController::class, 'store']);
    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'delete']);

    // Item
    Route::post('items', [ItemController::class, 'store']);
    Route::get('items', [ItemController::class, 'index']);
    Route::get('items/{item}', [ItemController::class, 'show']);
    Route::put('items/{item}', [ItemController::class, 'update']);
    Route::delete('items/{item}', [ItemController::class, 'delete']);

    // Item Brand
    Route::post('item-brands', [ItemBrandController::class, 'store']);
    Route::get('item-brands', [ItemBrandController::class, 'index']);
    Route::get('item-brands/{itemBrand}', [ItemBrandController::class, 'show']);
    Route::put('item-brands/{itemBrand}', [ItemBrandController::class, 'update']);
    Route::delete('item-brands/{itemBrand}', [ItemBrandController::class, 'delete']);

    // Item Category
    Route::post('item-categories', [ItemCategoryController::class, 'store']);
    Route::get('item-categories', [ItemCategoryController::class, 'index']);
    Route::get('item-categories/{itemCategory}', [ItemCategoryController::class, 'show']);
    Route::put('item-categories/{itemCategory}', [ItemCategoryController::class, 'update']);
    Route::delete('item-categories/{itemCategory}', [ItemCategoryController::class, 'delete']);

    // UOM
    Route::post('uoms', [UOMController::class, 'store']);
    Route::get('uoms', [UOMController::class, 'index']);
    Route::get('uoms/{uom}', [UOMController::class, 'show']);
    Route::put('uoms/{uom}', [UOMController::class, 'update']);
    Route::delete('uoms/{uom}', [UOMController::class, 'delete']);

    // Account
    Route::post('accounts', [AccountController::class, 'store']);
    Route::get('accounts', [AccountController::class, 'index']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::put('accounts/{account}', [AccountController::class, 'update']);
    Route::delete('accounts/{account}', [AccountController::class, 'delete']);

    // Inventory Engine
    Route::post('inventory/valuate', [InventoryController::class, 'valuate']);
    Route::post('inventory/allocate', [InventoryController::class, 'allocate']);
});
