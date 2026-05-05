<?php
$loader = require __DIR__ . '/vendor/autoload.php';
$file = $loader->findFile('Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverRepository');
echo "File: $file\n";
try {
    include $file;
    echo "Included OK\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
var_dump(class_exists('Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverRepository', false));
