<?php

if (! function_exists('module_path')) {
    /**
     * Get the path to a module directory.
     *
     * @param  string  $name  Module name
     * @param  string  $path  Additional path within the module
     */
    function module_path(string $name, string $path = ''): string
    {
        $modulesPath = base_path('Modules');
        $modulePath = $modulesPath.DIRECTORY_SEPARATOR.$name;

        if ($path) {
            return $modulePath.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $modulePath;
    }
}
