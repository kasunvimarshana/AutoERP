<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Support\Facades\Route;
use Modules\Sequence\Constants\SequencePermission;
use Modules\Sequence\Http\Controllers\SequenceController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
).':required';
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/sequence')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('api.v1.sequence.')
    ->group(function () use ($requires): void {
        Route::post('sequences/preview-number', [SequenceController::class, 'previewNumber'])
            ->middleware($requires(SequencePermission::VIEW))
            ->name('sequences.preview-number');
        Route::post('sequences/generate-number', [SequenceController::class, 'generateNumber'])
            ->middleware($requires(SequencePermission::MANAGE))
            ->name('sequences.generate-number');

        Route::get('sequences', [SequenceController::class, 'index'])
            ->middleware($requires(SequencePermission::VIEW))
            ->name('sequences.index');
        Route::post('sequences', [SequenceController::class, 'store'])
            ->middleware($requires(SequencePermission::MANAGE))
            ->name('sequences.store');
        Route::get('sequences/{sequence}', [SequenceController::class, 'show'])
            ->whereNumber('sequence')
            ->middleware($requires(SequencePermission::VIEW))
            ->name('sequences.show');
        Route::match(['put', 'patch'], 'sequences/{sequence}', [SequenceController::class, 'update'])
            ->whereNumber('sequence')
            ->middleware($requires(SequencePermission::MANAGE))
            ->name('sequences.update');
        Route::delete('sequences/{sequence}', [SequenceController::class, 'destroy'])
            ->whereNumber('sequence')
            ->middleware($requires(SequencePermission::MANAGE))
            ->name('sequences.destroy');
    });
