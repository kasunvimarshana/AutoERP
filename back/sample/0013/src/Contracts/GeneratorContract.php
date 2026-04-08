<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Contracts;

/**
 * GeneratorContract — Abstract contract for all file generators.
 *
 * Every generator (Entity, ValueObject, Command, etc.) must implement this
 * interface so that the package core can invoke them uniformly.
 */
interface GeneratorContract
{
    /**
     * Execute the generation process.
     *
     * @param  string  $context    Bounded context name (PascalCase)
     * @param  string  $className  Class name to generate (PascalCase)
     * @param  array   $options    Additional options (force, namespace, etc.)
     * @return bool  True on success, false when file already exists and force is off
     */
    public function generate(string $context, string $className, array $options = []): bool;

    /**
     * Return the stub identifier used by this generator.
     */
    public function stubKey(): string;

    /**
     * Return a human-readable label for console output.
     */
    public function label(): string;
}
