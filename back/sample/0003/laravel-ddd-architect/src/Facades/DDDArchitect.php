<?php

namespace YourVendor\LaravelDDDArchitect\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array  all()
 * @method static bool   exists(string $context)
 * @method static string path(string $context)
 * @method static string namespace(string $context)
 * @method static array  config()
 *
 * @see \YourVendor\LaravelDDDArchitect\Resolvers\ContextResolver
 */
class DDDArchitect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ddd-architect';
    }
}
