<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Facades;

use Archify\DddArchitect\Contracts\ContextRegistrar;
use Illuminate\Support\Facades\Facade;

/**
 * DddArchitect Facade
 *
 * Provides a static interface to the ContextRegistrar.
 *
 * Usage:
 *   DddArchitect::all();             // list all registered contexts
 *   DddArchitect::has('Ordering');   // check if a context exists
 *   DddArchitect::get('Ordering');   // get context metadata
 *
 * @method static void         register(string $name, array $metadata = [])
 * @method static array|null   get(string $name)
 * @method static array        all()
 * @method static bool         has(string $name)
 * @method static void         forget(string $name)
 *
 * @see ContextRegistrar
 */
final class DddArchitect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ContextRegistrar::class;
    }
}
