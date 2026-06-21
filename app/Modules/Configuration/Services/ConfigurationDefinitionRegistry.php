<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConfigurationDefinitionRegistry implements ConfigurationDefinitionRegistryInterface
{
    /** @var array<string, ConfigurationDefinition>|null */
    private ?array $definitions = null;

    public function __construct(
        private readonly ReferenceValueLookupInterface $referenceValues,
        private readonly ConfigurationValueValidator $values,
    ) {}

    public function get(string $key): ConfigurationDefinition
    {
        $key = strtolower(trim($key));
        $definition = $this->definitions()[$key] ?? null;

        return $definition instanceof ConfigurationDefinition
            ? $definition
            : throw new NotFoundHttpException(
                "Configuration definition [{$key}] was not found.",
            );
    }

    public function all(): array
    {
        return array_values($this->definitions());
    }

    /** @return array<string, ConfigurationDefinition> */
    private function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $rawDefinitions = config('configuration.definitions', []);
        if (! is_array($rawDefinitions)) {
            throw new InvalidArgumentException('Configuration definitions must be an array.');
        }

        $definitions = [];
        foreach ($rawDefinitions as $key => $raw) {
            $definition = $this->buildDefinition($key, $raw);
            $this->validateDefinitionValues($definition);
            $definitions[$definition->key] = $definition;
        }

        ksort($definitions);

        return $this->definitions = $definitions;
    }

    private function buildDefinition(mixed $key, mixed $raw): ConfigurationDefinition
    {
        if (
            ! is_string($key)
            || preg_match('/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)+$/', $key) !== 1
        ) {
            throw new InvalidArgumentException(
                'Configuration definition keys must be canonical lowercase namespaced keys.',
            );
        }
        if (! is_array($raw)) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] must be an array.",
            );
        }

        $type = (string) ($raw['type'] ?? '');
        if (! in_array($type, ConfigurationValueType::values(), true)) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] has an invalid value type.",
            );
        }

        $scopes = array_values(array_unique(array_filter(
            is_array($raw['scopes'] ?? null) ? $raw['scopes'] : [],
            static fn (mixed $scope): bool => is_string($scope)
                && in_array($scope, ConfigurationScope::values(), true),
        )));
        if ($scopes === []) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] must allow at least one scope.",
            );
        }

        $label = trim((string) ($raw['label'] ?? ''));
        $description = trim((string) ($raw['description'] ?? ''));
        $owner = trim((string) ($raw['owner'] ?? ''));
        if ($label === '' || $description === '' || $owner === '') {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] requires a label, description, and owner.",
            );
        }

        $options = is_array($raw['options'] ?? null)
            ? array_values($raw['options'])
            : [];
        foreach ($options as $option) {
            if (
                ! is_string($option)
                && ! is_int($option)
                && ! is_float($option)
                && ! is_bool($option)
            ) {
                throw new InvalidArgumentException(
                    "Configuration definition [{$key}] options must contain scalar values only.",
                );
            }
        }

        $lookup = is_string($raw['lookup'] ?? null)
            && trim($raw['lookup']) !== ''
                ? trim($raw['lookup'])
                : null;
        if ($lookup !== null && ! $this->referenceValues->supports($lookup)) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] references an unknown lookup [{$lookup}].",
            );
        }
        if ($lookup !== null && $options !== []) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] cannot define both options and a reference lookup.",
            );
        }

        $minimum = is_numeric($raw['minimum'] ?? null)
            ? (float) $raw['minimum']
            : null;
        $maximum = is_numeric($raw['maximum'] ?? null)
            ? (float) $raw['maximum']
            : null;
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] minimum cannot exceed its maximum.",
            );
        }

        return new ConfigurationDefinition(
            key: $key,
            label: $label,
            description: $description,
            owner: $owner,
            valueType: $type,
            allowedScopes: $scopes,
            defaultValue: $raw['default'] ?? null,
            nullable: (bool) ($raw['nullable'] ?? false),
            sensitive: (bool) ($raw['sensitive'] ?? false),
            runtimeMutable: (bool) ($raw['runtime_mutable'] ?? true),
            options: $options,
            minimum: $minimum,
            maximum: $maximum,
            lookup: $lookup,
        );
    }

    private function validateDefinitionValues(ConfigurationDefinition $definition): void
    {
        try {
            $this->values->validate($definition, $definition->defaultValue);

            foreach ($definition->options as $option) {
                $this->values->validate($definition, $option);
            }
        } catch (ValidationException $exception) {
            throw new InvalidArgumentException(
                "Configuration definition [{$definition->key}] contains an invalid default or option value.",
                previous: $exception,
            );
        }
    }
}
