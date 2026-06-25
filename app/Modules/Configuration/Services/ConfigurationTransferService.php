<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Core\Contracts\ClockInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ConfigurationTransferService
{
    public const SCHEMA_VERSION = 1;
    private const MAX_ENTRIES = 500;

    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationValueRepositoryInterface $values,
        private readonly ConfigurationValueValidator $validator,
        private readonly ConfigurationValueCodec $codec,
        private readonly ConfigurationAuthorizationService $authorization,
        private readonly ConfigurationEntryService $entries,
        private readonly ClockInterface $clock,
        private readonly AuditRecorderInterface $audit,
    ) {}

    /** @return array<string, mixed> */
    public function exportGlobal(): array
    {
        $this->assertCanView();
        $context = $this->globalContext();
        $entries = [];

        foreach ($this->definitions->all() as $definition) {
            if (! $this->transferable($definition)) {
                continue;
            }
            $stored = $this->values->findExact($context, $definition->key);
            if ($stored === null) {
                continue;
            }
            $entries[] = [
                'key' => $definition->key,
                'value' => $this->codec->decode($definition, $stored->storedValue),
            ];
        }

        usort($entries, static fn (array $left, array $right): int => $left['key'] <=> $right['key']);

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'scope' => ConfigurationScope::GLOBAL,
            'generated_at' => $this->clock->now()->format(DATE_ATOM),
            'sensitive_values_included' => false,
            'entries' => $entries,
        ];
    }

    /** @param array<string, mixed> $document @return array<string, mixed> */
    public function previewGlobal(array $document): array
    {
        $this->assertCanView();
        $normalized = $this->normalizeDocument($document);
        $context = $this->globalContext();
        $previewEntries = [];
        $state = [];

        foreach ($normalized['entries'] as $entry) {
            $definition = $this->transferDefinition($entry['key']);
            $value = $this->validator->validate($definition, $entry['value']);
            $current = $this->values->findExact($context, $definition->key);
            $before = $current === null ? null : $this->codec->decode($definition, $current->storedValue);
            $action = $current === null ? 'create' : ($before === $value ? 'unchanged' : 'update');

            $previewEntries[] = [
                'key' => $definition->key,
                'label' => $definition->label,
                'owner' => $definition->owner,
                'action' => $action,
                'current_value' => $before,
                'import_value' => $value,
                'current_version' => $current?->rowVersion,
            ];
            $state[] = [
                'key' => $definition->key,
                'value' => $value,
                'current_version' => $current?->rowVersion,
            ];
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'scope' => ConfigurationScope::GLOBAL,
            'confirmation_digest' => $this->digest($state),
            'summary' => [
                'total' => count($previewEntries),
                'create' => $this->countAction($previewEntries, 'create'),
                'update' => $this->countAction($previewEntries, 'update'),
                'unchanged' => $this->countAction($previewEntries, 'unchanged'),
            ],
            'entries' => $previewEntries,
        ];
    }

    /** @param array<string, mixed> $document @return array<string, int> */
    public function applyGlobal(array $document, string $confirmationDigest, string $reason): array
    {
        if (! $this->authorization->canManagePlatformScope(ConfigurationScope::GLOBAL)) {
            throw new AuthorizationException('Importing global configuration is not authorized.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'reason' => ['Enter at least 10 characters describing why this import is required.'],
            ]);
        }

        $preview = $this->previewGlobal($document);
        if (! hash_equals((string) $preview['confirmation_digest'], strtolower(trim($confirmationDigest)))) {
            throw new ConflictHttpException('Configuration changed after the import preview. Generate a new preview and review it again.');
        }

        /** @var list<array<string, mixed>> $previewEntries */
        $previewEntries = $preview['entries'];
        $result = DB::transaction(function () use ($previewEntries): array {
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            foreach ($previewEntries as $entry) {
                $action = (string) $entry['action'];
                if ($action === 'create') {
                    $this->entries->createPlatform(
                        ConfigurationScope::GLOBAL,
                        null,
                        null,
                        (string) $entry['key'],
                        $entry['import_value'],
                    );
                    $created++;
                    continue;
                }
                if ($action === 'update') {
                    $this->entries->updatePlatform(
                        ConfigurationScope::GLOBAL,
                        null,
                        null,
                        (string) $entry['key'],
                        (int) $entry['current_version'],
                        $entry['import_value'],
                    );
                    $updated++;
                    continue;
                }
                $unchanged++;
            }

            return compact('created', 'updated', 'unchanged');
        }, 3);

        $this->audit->recordPlatform(new AuditEventData(
            eventName: 'configuration.global_import_applied',
            eventCategory: AuditEventCategory::CONFIGURATION,
            sourceModule: 'configuration',
            subjectType: 'configuration_transfer',
            subjectId: (string) $this->clock->now()->getTimestamp(),
            subjectReference: 'Global configuration import',
            changes: $result,
            metadata: [
                'reason' => $reason,
                'schema_version' => self::SCHEMA_VERSION,
                'entry_count' => count($previewEntries),
            ],
            tags: ['configuration', 'platform', 'import'],
        ));

        return $result;
    }

    private function assertCanView(): void
    {
        if (! $this->authorization->canViewPlatformScope(ConfigurationScope::GLOBAL)) {
            throw new AuthorizationException('Exporting or previewing global configuration is not authorized.');
        }
    }

    /** @param array<string, mixed> $document @return array{entries:list<array{key:string,value:mixed}>} */
    private function normalizeDocument(array $document): array
    {
        if (($document['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            throw ValidationException::withMessages([
                'document.schema_version' => ['The configuration export schema version is not supported.'],
            ]);
        }
        if (($document['scope'] ?? null) !== ConfigurationScope::GLOBAL) {
            throw ValidationException::withMessages([
                'document.scope' => ['Only global configuration exports can be imported here.'],
            ]);
        }
        $rawEntries = $document['entries'] ?? null;
        if (! is_array($rawEntries) || count($rawEntries) > self::MAX_ENTRIES) {
            throw ValidationException::withMessages([
                'document.entries' => ['Provide at most '.self::MAX_ENTRIES.' configuration entries.'],
            ]);
        }

        $normalized = [];
        foreach (array_values($rawEntries) as $index => $raw) {
            if (! is_array($raw)) {
                throw ValidationException::withMessages([
                    "document.entries.{$index}" => ['Each configuration entry must be an object.'],
                ]);
            }
            $key = strtolower(trim((string) ($raw['key'] ?? '')));
            if ($key === '') {
                throw ValidationException::withMessages([
                    "document.entries.{$index}.key" => ['A registered configuration key is required.'],
                ]);
            }
            if (array_key_exists($key, $normalized)) {
                throw ValidationException::withMessages([
                    "document.entries.{$index}.key" => ['Duplicate configuration keys are not allowed.'],
                ]);
            }
            if (! array_key_exists('value', $raw)) {
                throw ValidationException::withMessages([
                    "document.entries.{$index}.value" => ['The configuration value is required.'],
                ]);
            }
            $normalized[$key] = ['key' => $key, 'value' => $raw['value']];
        }
        ksort($normalized);

        return ['entries' => array_values($normalized)];
    }

    private function transferDefinition(string $key): ConfigurationDefinition
    {
        $definition = $this->definitions->get($key);
        if (! $this->transferable($definition)) {
            throw ValidationException::withMessages([
                'document.entries' => ["Setting [{$key}] is protected, immutable, or unavailable at global scope and cannot be transferred."],
            ]);
        }

        return $definition;
    }

    private function transferable(ConfigurationDefinition $definition): bool
    {
        return ! $definition->sensitive
            && $definition->runtimeMutable
            && in_array(ConfigurationScope::GLOBAL, $definition->allowedScopes, true);
    }

    /** @param list<array<string, mixed>> $entries */
    private function countAction(array $entries, string $action): int
    {
        return count(array_filter($entries, static fn (array $entry): bool => $entry['action'] === $action));
    }

    /** @param list<array<string, mixed>> $state */
    private function digest(array $state): string
    {
        $key = (string) config('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? '' : $decoded;
        }
        if ($key === '') {
            throw new \RuntimeException('Application encryption key is required for configuration import confirmation.');
        }

        try {
            $payload = json_encode($this->canonicalize($state), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                'document' => ['The configuration document contains an unsupported value.'],
            ]);
        }

        return hash_hmac('sha256', $payload, $key);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $entry): mixed => $this->canonicalize($entry), $value);
        }
        ksort($value);
        foreach ($value as $key => $entry) {
            $value[$key] = $this->canonicalize($entry);
        }

        return $value;
    }

    private function globalContext(): ConfigurationScopeContext
    {
        return new ConfigurationScopeContext(ConfigurationScope::GLOBAL, null, null);
    }
}
