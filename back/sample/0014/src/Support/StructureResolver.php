<?php

namespace YourVendor\LaravelDDDArchitect\Support;

/**
 * StructureResolver merges the active structure preset over the root
 * configuration array so callers always get the correct resolved config.
 *
 * Priority (highest → lowest):
 *   1. Keys defined in the active preset inside `structure_choices`
 *   2. Root-level keys in `config/ddd-architect.php`
 *   3. Hard-coded package defaults
 */
class StructureResolver
{
    /**
     * Resolve the effective configuration by merging the selected preset
     * over the root-level defaults.
     *
     * @param  array  $config  Full ddd-architect config array
     * @return array           Effective merged configuration
     */
    public static function resolve(array $config): array
    {
        $preset = $config['structure'] ?? 'ddd-layered';
        $choices = $config['structure_choices'] ?? [];

        // No-op when preset is not defined or is 'custom' with no overrides
        if (! isset($choices[$preset]) || empty($choices[$preset])) {
            return $config;
        }

        // Merge preset keys over root config — preset wins
        return array_merge($config, $choices[$preset]);
    }

    /**
     * Return all available preset names.
     *
     * @param  array  $config
     * @return array<string>
     */
    public static function availablePresets(array $config): array
    {
        return array_keys($config['structure_choices'] ?? []);
    }

    /**
     * Check whether a named preset exists.
     */
    public static function presetExists(array $config, string $preset): bool
    {
        return isset($config['structure_choices'][$preset]);
    }
}
