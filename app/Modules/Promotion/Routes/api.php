<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Promotion\Constants\PromotionPermission;
use Modules\Promotion\Http\Controllers\PromotionController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:vehicle-service',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/promotions')->middleware($middleware)->name('api.v1.promotions.')->group(function () use ($requires): void {
    Route::get('job-items/lookup', [PromotionController::class, 'jobItems'])->name('job-items.lookup');
    Route::get('available', [PromotionController::class, 'available'])->name('available');
    Route::post('offers/{offer}/assignments', [PromotionController::class, 'assignOffer'])->whereNumber('offer')->name('offers.assign');
    Route::middleware($requires(PromotionPermission::VIEW))->group(function (): void {
        Route::get('offers', [PromotionController::class, 'offers'])->name('offers.index');
        Route::get('card-templates', [PromotionController::class, 'templates'])->name('templates.index');
        Route::get('cards', [PromotionController::class, 'cards'])->name('cards.index');
    });
    Route::middleware($requires(PromotionPermission::MANAGE))->group(function (): void {
        Route::post('offers', [PromotionController::class, 'createOffer'])->name('offers.store');
        Route::patch('offers/{offer}/status', [PromotionController::class, 'offerStatus'])->whereNumber('offer')->name('offers.status');
        Route::delete('offers/{offer}', [PromotionController::class, 'deleteOffer'])->whereNumber('offer')->name('offers.destroy');
        Route::post('card-templates', [PromotionController::class, 'createTemplate'])->name('templates.store');
        Route::post('cards', [PromotionController::class, 'issueCard'])->name('cards.store');
        Route::patch('cards/{card}/transfer', [PromotionController::class, 'transferCard'])->whereNumber('card')->name('cards.transfer');
        Route::patch('cards/{card}/status', [PromotionController::class, 'cardStatus'])->whereNumber('card')->name('cards.status');
    });
    Route::put('jobs/{job}/lines/{line}/choice', [PromotionController::class, 'choose'])
        ->whereNumber('job')->whereNumber('line')->middleware($requires(PromotionPermission::REDEEM))->name('jobs.choice');
    Route::delete('jobs/{job}/lines/{line}/choice', [PromotionController::class, 'removeChoice'])
        ->whereNumber('job')->whereNumber('line')->middleware($requires(PromotionPermission::REDEEM))->name('jobs.choice.remove');
});
