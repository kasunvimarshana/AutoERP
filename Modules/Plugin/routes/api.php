<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Plugin\Interfaces\Http\Controllers\PluginController;

/*
|--------------------------------------------------------------------------
| Plugin Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('api')->prefix('api/v1')->name('plugin.')->group(function (): void {
    Route::get('plugins/tenant/enabled', [PluginController::class, 'listTenantPlugins'])->name('plugins.tenant.enabled');
    Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
    Route::post('plugins', [PluginController::class, 'install'])->name('plugins.install');
    Route::get('plugins/{id}', [PluginController::class, 'showPlugin'])->name('plugins.show');
    Route::put('plugins/{id}', [PluginController::class, 'updatePlugin'])->name('plugins.update');
    Route::delete('plugins/{id}', [PluginController::class, 'uninstallPlugin'])->name('plugins.uninstall');
    Route::post('plugins/{id}/enable', [PluginController::class, 'enable'])->name('plugins.enable');
    Route::post('plugins/{id}/disable', [PluginController::class, 'disable'])->name('plugins.disable');
});
