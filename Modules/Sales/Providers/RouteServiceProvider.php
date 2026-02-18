<?php

namespace Modules\Sales\Providers;

use Modules\Core\Providers\ModuleRouteServiceProvider;

class RouteServiceProvider extends ModuleRouteServiceProvider
{
    /**
     * The module name.
     */
    protected string $moduleName = 'Sales';

    /**
     * The module namespace to assume when generating URLs to actions.
     */
    protected string $moduleNamespace = 'Modules\Sales\Http\Controllers';
}
