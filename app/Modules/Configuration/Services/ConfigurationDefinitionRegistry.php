<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConfigurationDefinitionRegistry implements ConfigurationDefinitionRegistryInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $registeredDefinitions = [];

    /** @var array<string, ConfigurationDefinition>|null */
    private ?array $definitions = null;

    private bool $frozen = false;

    public function __construct(
        private readonly ReferenceValueLookupInterface $referenceValues,
        private readonly ConfigurationValueValidator $values,
    ) {}

    public function register(string $owner, array $definitions): void
    {
        if ($this->frozen || $this->definitions !== null) {
            throw new LogicException('Configuration definitions cannot be registered after the registry is frozen.');
        }

        $owner = trim($owner);
        if ($owner === '') {
            throw new InvalidArgumentException('Configuration definition owner is required.');
        }

        foreach ($definitions as $key => $raw) {
            if (! is_string($key) || ! is_array($raw)) {
                throw new InvalidArgumentException('Configuration definitions must use string keys and array metadata.');
            }
            if (array_key_exists($key, $this->registeredDefinitions)) {
                throw new LogicException("Configuration definition [{$key}] is already registered.");
            }

            $declaredOwner = trim((string) ($raw['owner'] ?? $owner));
            if ($declaredOwner !== $owner) {
                throw new InvalidArgumentException("Configuration definition [{$key}] owner does not match its registering module.");
            }

            $raw['owner'] = $owner;
            $this->registeredDefinitions[$key] = $raw;
        }
    }

    public function get(string $key): ConfigurationDefinition
    {
        $key = trim($key);
        if (preg_match('/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)+$/', $key) !== 1) {
            throw ValidationException::withMessages([
                'key' => ['Configuration keys must be canonical namespaced strings such as localization.timezone.'],
            ]);
        }

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

        $this->frozen = true;

        $definitions = [];
        foreach ($this->registeredDefinitions as $key => $raw) {
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

        $version = $raw['version'] ?? null;
        if (! is_int($version) || $version < 1) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] requires a positive integer version.",
            );
        }

        $minimum = $this->decimalBoundary($raw['minimum'] ?? null, $key, 'minimum');
        $maximum = $this->decimalBoundary($raw['maximum'] ?? null, $key, 'maximum');
        if (
            $minimum !== null
            && $maximum !== null
            && $this->values->compareNumericBoundaries($minimum, $maximum) > 0
        ) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] minimum cannot exceed its maximum.",
            );
        }

        foreach (['sensitive', 'runtime_mutable', 'inherit_organization_hierarchy'] as $requiredFlag) {
            if (! array_key_exists($requiredFlag, $raw) || ! is_bool($raw[$requiredFlag])) {
                throw new InvalidArgumentException(
                    "Configuration definition [{$key}] must explicitly declare boolean [{$requiredFlag}].",
                );
            }
        }

        if (
            $raw['inherit_organization_hierarchy']
            && ! in_array(ConfigurationScope::ORGANIZATION_UNIT, $scopes, true)
        ) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] cannot inherit the organization hierarchy without organization-unit scope.",
            );
        }

        return new ConfigurationDefinition(
            key: $key,
            label: $label,
            description: $description,
            owner: $owner,
            version: $version,
            valueType: $type,
            allowedScopes: $scopes,
            defaultValue: $raw['default'] ?? null,
            nullable: (bool) ($raw['nullable'] ?? false),
            sensitive: $raw['sensitive'],
            runtimeMutable: $raw['runtime_mutable'],
            inheritOrganizationHierarchy: $raw['inherit_organization_hierarchy'],
            options: $options,
            minimum: $minimum,
            maximum: $maximum,
            lookup: $lookup,
        );
    }


    private function decimalBoundary(mixed $value, string $key, string $name): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_int($value) && ! is_string($value)) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] {$name} must be a plain integer or decimal string.",
            );
        }

        $normalized = trim((string) $value);
        if (preg_match('/^-?\d+(?:\.\d+)?$/D', $normalized) !== 1) {
            throw new InvalidArgumentException(
                "Configuration definition [{$key}] {$name} must be a plain decimal without scientific notation.",
            );
        }

        return $normalized;
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
