<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Expense\Constants\ExpensePermission;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Expense\Http\Controllers\ExpenseTypeController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:finance',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/expenses')->middleware($middleware)->name('api.v1.expenses.')->group(function () use ($requires): void {
    Route::get('type-options', [ExpenseTypeController::class, 'options'])
        ->middleware($requires(ExpensePermission::EXPENSES_CREATE))
        ->name('type-options');
    Route::get('types', [ExpenseTypeController::class, 'index'])
        ->middleware($requires(ExpensePermission::TYPES_VIEW))
        ->name('types.index');
    Route::post('types', [ExpenseTypeController::class, 'store'])
        ->middleware($requires(ExpensePermission::TYPES_MANAGE))
        ->name('types.store');
    Route::put('types/{expenseType}', [ExpenseTypeController::class, 'update'])
        ->whereNumber('expenseType')
        ->middleware($requires(ExpensePermission::TYPES_MANAGE))
        ->name('types.update');
    Route::get('payment-methods', [ExpenseController::class, 'paymentMethods'])
        ->middleware($requires(ExpensePermission::EXPENSES_CREATE))
        ->name('payment-methods');
    Route::get('/', [ExpenseController::class, 'index'])
        ->middleware($requires(ExpensePermission::EXPENSES_VIEW))
        ->name('index');
    Route::post('/', [ExpenseController::class, 'store'])
        ->middleware($requires(ExpensePermission::EXPENSES_CREATE))
        ->name('store');
    Route::get('{expense}', [ExpenseController::class, 'show'])
        ->whereNumber('expense')
        ->middleware($requires(ExpensePermission::EXPENSES_VIEW))
        ->name('show');
    Route::post('{expense}/reverse', [ExpenseController::class, 'reverse'])
        ->whereNumber('expense')
        ->middleware($requires(ExpensePermission::EXPENSES_REVERSE))
        ->name('reverse');
});
