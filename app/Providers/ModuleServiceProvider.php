<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register all module service providers found in Modules/.
     */
    public function register(): void
    {
        $modulesPath = base_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        foreach (glob("{$modulesPath}/*/Providers/*ServiceProvider.php") as $file) {
            $relative = str_replace(base_path().'/', '', $file);
            $class = $this->pathToClass($relative);

            if (class_exists($class)) {
                $this->app->register($class);
            }
        }
    }

    /**
     * Convert a file path to a fully qualified class name.
     */
    private function pathToClass(string $relative): string
    {
        return str_replace(['/', '.php'], ['\\', ''], ucfirst($relative));
    }
}
