<?php
namespace App\Providers;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulesPath = base_path('Modules');
        if (! File::isDirectory($modulesPath)) {
            return;
        }
        foreach (File::directories($modulesPath) as $modulePath) {
            $moduleName = basename($modulePath);
            if ($moduleName === 'Shared') {
                continue;
            }
            $providerClass = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
    public function boot(): void {}
}
