<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class GenerateErpModulesCommand extends Command
{
    protected $signature = 'erp:generate-modules
        {--module=* : Specific module names to generate}
        {--force : Overwrite existing generated files}
        {--skip-provider-sync : Do not update bootstrap/providers.php}';

    protected $description = 'Generate full ERP module layers from module migration files using Core/Configuration/Tenant patterns.';

    private const REFERENCE_MODULES = ['Core', 'Configuration', 'Tenant'];

    public function handle(): int
    {
        $modulesPath = base_path('app/Modules');
        if (! is_dir($modulesPath)) {
            $this->error('Modules path not found: ' . $modulesPath);

            return self::FAILURE;
        }

        $requested = collect((array) $this->option('module'))
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();

        $moduleNames = $this->discoverModules($modulesPath, $requested);

        if ($moduleNames === []) {
            $this->warn('No target modules found with migration files.');

            return self::SUCCESS;
        }

        $generatedProviders = [];
        $force = (bool) $this->option('force');

        foreach ($moduleNames as $moduleName) {
            $tables = $this->extractTablesFromModuleMigrations($moduleName);
            if ($tables === []) {
                $this->warn("Skipping {$moduleName}: no Schema::create tables discovered.");

                continue;
            }

            $this->generateModule($moduleName, $tables, $force);
            $generatedProviders[] = "Modules\\{$moduleName}\\Infrastructure\\Providers\\{$moduleName}ServiceProvider::class";
            $this->info("Generated module scaffold: {$moduleName} (" . count($tables) . ' tables)');
        }

        if (! (bool) $this->option('skip-provider-sync') && $generatedProviders !== []) {
            $this->syncBootstrapProviders($generatedProviders);
        }

        $this->info('ERP module generation completed.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function discoverModules(string $modulesPath, array $requested): array
    {
        $all = collect(File::directories($modulesPath))
            ->map(fn ($path) => basename($path))
            ->filter(fn ($name) => ! in_array($name, self::REFERENCE_MODULES, true))
            ->values();

        if ($requested !== []) {
            $requestedLookup = array_fill_keys($requested, true);

            $all = $all->filter(fn ($name) => array_key_exists($name, $requestedLookup))->values();
        }

        return $all
            ->filter(function (string $moduleName): bool {
                $migrationPath = base_path("app/Modules/{$moduleName}/Infrastructure/Persistence/Eloquent/Migrations");

                return is_dir($migrationPath) && glob($migrationPath . '/*.php') !== false;
            })
            ->all();
    }

    /**
     * @return list<array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * }>
     */
    private function extractTablesFromModuleMigrations(string $moduleName): array
    {
        $migrationPath = base_path("app/Modules/{$moduleName}/Infrastructure/Persistence/Eloquent/Migrations");
        $files = glob($migrationPath . '/*.php') ?: [];
        sort($files);

        $tables = [];

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            if ($content === '' || ! str_contains($content, 'Schema::create(')) {
                continue;
            }

            if (! preg_match("/Schema::create\\('([^']+)'/", $content, $tableMatch)) {
                continue;
            }

            $table = $tableMatch[1];
            $columns = $this->parseColumns($content);
            if ($columns === []) {
                continue;
            }

            $entity = Str::studly(Str::singular($table));
            $plural = Str::pluralStudly($entity);

            $tables[] = [
                'table' => $table,
                'entity' => $entity,
                'plural' => $plural,
                'route' => Str::kebab(Str::snake($plural)),
                'softDeletes' => str_contains($content, '->softDeletes(') || str_contains($content, '->softDeletes()'),
                'tenantAware' => collect($columns)->contains(fn (array $column) => $column['name'] === 'tenant_id'),
                'columns' => $columns,
            ];
        }

        return $tables;
    }

    /**
     * @return list<array{name:string,type:string,nullable:bool,default:bool}>
     */
    private function parseColumns(string $content): array
    {
        $columns = [];
        $lines = preg_split('/\R/', $content) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (! str_contains($trimmed, '$table->')) {
                continue;
            }

            if (
                str_contains($trimmed, '$table->index(')
                || str_contains($trimmed, '$table->unique(')
                || str_contains($trimmed, '$table->foreign(')
                || str_contains($trimmed, '$table->primary(')
            ) {
                continue;
            }

            if (preg_match('/\$table->id\(/', $trimmed)) {
                $columns[] = ['name' => 'id', 'type' => 'id', 'nullable' => false, 'default' => false];
                continue;
            }

            if (preg_match('/\$table->timestamps\(/', $trimmed)) {
                $columns[] = ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true, 'default' => false];
                $columns[] = ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true, 'default' => false];
                continue;
            }

            if (preg_match('/\$table->softDeletes\(/', $trimmed)) {
                $columns[] = ['name' => 'deleted_at', 'type' => 'timestamp', 'nullable' => true, 'default' => false];
                continue;
            }

            if (! preg_match('/\$table->([A-Za-z_][A-Za-z0-9_]*)\(\'([^\']+)\'/', $trimmed, $match)) {
                continue;
            }

            $method = $match[1];
            $name = $match[2];

            $columns[] = [
                'name' => $name,
                'type' => $method,
                'nullable' => str_contains($trimmed, '->nullable(') || str_contains($trimmed, '->nullable()'),
                'default' => str_contains($trimmed, '->default('),
            ];
        }

        $deduped = [];
        $seen = [];

        foreach ($columns as $column) {
            if (array_key_exists($column['name'], $seen)) {
                continue;
            }

            $seen[$column['name']] = true;
            $deduped[] = $column;
        }

        return $deduped;
    }

    /**
     * @param list<array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * }> $tables
     */
    private function generateModule(string $moduleName, array $tables, bool $force): void
    {
        $base = base_path("app/Modules/{$moduleName}");

        $directories = [
            "{$base}/Domain/Entities",
            "{$base}/Domain/Constants",
            "{$base}/Domain/Contracts",
            "{$base}/Domain/Services",
            "{$base}/Application/DTOs",
            "{$base}/Application/Repositories",
            "{$base}/Application/Support",
            "{$base}/Application/Contracts/UseCases",
            "{$base}/Application/UseCases",
            "{$base}/Infrastructure/Persistence/Eloquent/Models",
            "{$base}/Infrastructure/Persistence/Eloquent/Repositories",
            "{$base}/Infrastructure/Providers",
            "{$base}/Infrastructure/Config",
            "{$base}/Presentation/Http/Controllers",
            "{$base}/Presentation/Http/Requests",
            "{$base}/Presentation/Http/Resources",
            "{$base}/routes",
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists($directory);
        }

        $this->writeFileIfAllowed(
            "{$base}/Domain/Constants/{$moduleName}ErrorCode.php",
            $this->buildErrorCode($moduleName),
            $force,
        );

        $this->writeFileIfAllowed(
            "{$base}/Infrastructure/Config/" . Str::snake($moduleName) . '.php',
            $this->buildConfigFile($moduleName),
            $force,
        );

        $this->writeFileIfAllowed(
            "{$base}/Presentation/Http/Resources/DataRecordResource.php",
            $this->buildDataRecordResource($moduleName),
            $force,
        );

        foreach ($tables as $table) {
            $entity = $table['entity'];
            $plural = $table['plural'];

            File::ensureDirectoryExists("{$base}/Application/Contracts/UseCases/{$plural}");
            File::ensureDirectoryExists("{$base}/Application/UseCases/{$plural}");

            $this->writeFileIfAllowed(
                "{$base}/Domain/Entities/{$entity}.php",
                $this->buildDomainEntity($moduleName, $entity, $table['columns']),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Domain/Contracts/{$entity}DomainServiceInterface.php",
                $this->buildDomainServiceInterface($moduleName, $entity),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Domain/Services/{$entity}DomainService.php",
                $this->buildDomainService($moduleName, $entity),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Application/DTOs/{$entity}MutationData.php",
                $this->buildMutationDto($moduleName, $entity, $table['columns']),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Application/DTOs/{$entity}QueryData.php",
                $this->buildQueryDto($moduleName, $entity, $table['tenantAware']),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Application/DTOs/{$entity}ValueData.php",
                $this->buildValueDto($moduleName, $entity, $table['columns']),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Application/Support/{$entity}RecordMapper.php",
                $this->buildRecordMapper($moduleName, $entity),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Application/Repositories/{$entity}RepositoryInterface.php",
                $this->buildRepositoryInterface($moduleName, $entity, $table),
                $force,
            );

            $this->writeCrudContracts($moduleName, $entity, $plural, $force);
            $this->writeCrudUseCases($moduleName, $entity, $plural, $table, $force);

            $this->writeFileIfAllowed(
                "{$base}/Infrastructure/Persistence/Eloquent/Models/{$entity}Model.php",
                $this->buildModel($moduleName, $table, $tables),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Infrastructure/Persistence/Eloquent/Repositories/Eloquent{$entity}Repository.php",
                $this->buildEloquentRepository($moduleName, $entity, $table),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Presentation/Http/Requests/List{$entity}Request.php",
                $this->buildListRequest($moduleName, $entity, $table['tenantAware']),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Presentation/Http/Requests/Upsert{$entity}Request.php",
                $this->buildUpsertRequest($moduleName, $entity, $table['columns']),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Presentation/Http/Resources/{$entity}Resource.php",
                $this->buildResource($moduleName, $entity),
                $force,
            );

            $this->writeFileIfAllowed(
                "{$base}/Presentation/Http/Controllers/{$entity}Controller.php",
                $this->buildController($moduleName, $entity, $plural),
                $force,
            );
        }

        $this->writeFileIfAllowed("{$base}/routes/api.php", $this->buildRoutesFile($moduleName, $tables), $force);

        $this->writeFileIfAllowed(
            "{$base}/Infrastructure/Providers/{$moduleName}ServiceProvider.php",
            $this->buildServiceProvider($moduleName, $tables),
            $force,
        );

        $this->writeFileIfAllowed("{$base}/README.md", $this->buildReadme($moduleName, $tables), $force);
    }

    private function writeCrudContracts(string $moduleName, string $entity, string $plural, bool $force): void
    {
        $base = base_path("app/Modules/{$moduleName}/Application/Contracts/UseCases/{$plural}");

        $files = [
            "List{$plural}ServiceInterface.php" => $this->buildListContract($moduleName, $plural),
            "Get{$entity}ServiceInterface.php" => $this->buildGetContract($moduleName, $plural, $entity),
            "Create{$entity}ServiceInterface.php" => $this->buildCreateContract($moduleName, $plural, $entity),
            "Update{$entity}ServiceInterface.php" => $this->buildUpdateContract($moduleName, $plural, $entity),
            "Delete{$entity}ServiceInterface.php" => $this->buildDeleteContract($moduleName, $plural, $entity),
        ];

        foreach ($files as $filename => $content) {
            $this->writeFileIfAllowed("{$base}/{$filename}", $content, $force);
        }
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function writeCrudUseCases(
        string $moduleName,
        string $entity,
        string $plural,
        array $table,
        bool $force,
    ): void {
        $base = base_path("app/Modules/{$moduleName}/Application/UseCases/{$plural}");
        $tenantAware = $table['tenantAware'];

        $files = [
            "List{$plural}Service.php" => $this->buildListService($moduleName, $entity, $plural, $tenantAware),
            "Get{$entity}Service.php" => $this->buildGetService($moduleName, $entity, $plural, $tenantAware),
            "Create{$entity}Service.php" => $this->buildCreateService($moduleName, $entity, $plural, $table),
            "Update{$entity}Service.php" => $this->buildUpdateService($moduleName, $entity, $plural, $table),
            "Delete{$entity}Service.php" => $this->buildDeleteService($moduleName, $entity, $plural, $tenantAware),
        ];

        foreach ($files as $filename => $content) {
            $this->writeFileIfAllowed("{$base}/{$filename}", $content, $force);
        }
    }

    private function writeFileIfAllowed(string $path, string $content, bool $force): void
    {
        if (is_file($path) && ! $force) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    private function buildErrorCode(string $moduleName): string
    {
        $class = $moduleName . 'ErrorCode';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Domain\\Constants;

final class {$class}
{
    public const NOT_FOUND = 'NOT_FOUND';
    public const INVALID_VALUE = 'INVALID_VALUE';
    public const CONFLICT = 'CONFLICT';
}
PHP;
    }

    private function buildConfigFile(string $moduleName): string
    {
        $key = Str::snake($moduleName);

        return <<<PHP
<?php

declare(strict_types=1);

return [
    'module' => '{$key}',
];
PHP;
    }

    /**
     * @param list<array{name:string,type:string,nullable:bool,default:bool}> $columns
     */
    private function buildDomainEntity(string $moduleName, string $entity, array $columns): string
    {
        $ctorParams = [];
        $fromArrayLines = [];
        $toArrayLines = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $property = Str::camel($name);
            $type = $this->toPhpType($column['type'], $column['nullable'] || $name !== 'id');
            $sourceExpr = "\$payload['{$name}'] ?? null";

            $ctorParams[] = '        public ' . $type . ' $' . $property . ',';
            $fromArrayLines[] = '            ' . $this->castExpression($column, $sourceExpr) . ',';
            $toArrayLines[] = "            '{$name}' => \$this->{$property},";
        }

        $ctorParamsText = implode("\n", $ctorParams);
        $fromArrayText = implode("\n", $fromArrayLines);
        $toArrayText = implode("\n", $toArrayLines);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Domain\\Entities;

final readonly class {$entity}
{
    public function __construct(
{$ctorParamsText}
    ) {
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public static function fromArray(array \$payload): self
    {
        return new self(
{$fromArrayText}
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
{$toArrayText}
        ];
    }
}
PHP;
    }

    private function buildDataRecordResource(string $moduleName): string
    {
        $namespace = "Modules\\{$moduleName}\\Presentation\\Http\\Resources";

        return "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . "namespace {$namespace};\n\n"
            . "use Illuminate\\Http\\Request;\n"
            . "use Illuminate\\Http\\Resources\\Json\\JsonResource;\n"
            . "use Modules\\Core\\Application\\DTO\\DataRecord;\n\n"
            . "abstract class DataRecordResource extends JsonResource\n"
            . "{\n"
            . "    /**\n"
            . "     * @return array<string, mixed>\n"
            . "     */\n"
            . "    public function toArray(Request \$request): array\n"
            . "    {\n"
            . "        if (\$this->resource instanceof DataRecord) {\n"
            . "            return \$this->resource->toArray();\n"
            . "        }\n\n"
            . "        if (is_array(\$this->resource)) {\n"
            . "            return \$this->resource;\n"
            . "        }\n\n"
            . "        return [];\n"
            . "    }\n"
            . "}\n";
    }

    private function buildDomainServiceInterface(string $moduleName, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Domain\\Contracts;

interface {$entity}DomainServiceInterface
{
    public function normalizeOptionalText(?string \$value): ?string;

    /**
     * @param mixed \$value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed \$value): array;
}
PHP;
    }

    private function buildDomainService(string $moduleName, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Domain\\Services;

use Modules\\{$moduleName}\\Domain\\Contracts\\{$entity}DomainServiceInterface;

final class {$entity}DomainService implements {$entity}DomainServiceInterface
{
    public function normalizeOptionalText(?string \$value): ?string
    {
        if (\$value === null) {
            return null;
        }

        \$trimmed = trim(\$value);

        return \$trimmed === '' ? null : \$trimmed;
    }

    /**
     * @param mixed \$value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed \$value): array
    {
        return is_array(\$value) ? \$value : [];
    }
}
PHP;
    }

    /**
     * @param list<array{name:string,type:string,nullable:bool,default:bool}> $columns
     */
    private function buildMutationDto(string $moduleName, string $entity, array $columns): string
    {
        $params = [];
        $from = [];

        foreach ($columns as $column) {
            if (in_array($column['name'], ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $property = Str::camel($column['name']);
            $type = $this->toPhpType($column['type'], true);
            $sourceExpr = '$payload[\'' . $column['name'] . '\'] ?? null';
            $params[] = '        public ' . $type . ' $' . $property . ',';
            $from[] = '            ' . $this->castExpression($column, $sourceExpr) . ',';
        }

        $paramsText = implode("\n", $params);
        $fromText = implode("\n", $from);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\DTOs;

final readonly class {$entity}MutationData
{
    public function __construct(
{$paramsText}
    ) {
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public static function fromArray(array \$payload): self
    {
        return new self(
{$fromText}
        );
    }
}
PHP;
    }

    private function buildQueryDto(string $moduleName, string $entity, bool $tenantAware): string
    {
        $tenantLine = $tenantAware
            ? '            isset($payload[\'tenant_id\']) ? (int) $payload[\'tenant_id\'] : null,' . "\n"
            : '            null,' . "\n";

        $perPageLine = '            isset($payload[\'per_page\']) ? (int) $payload[\'per_page\'] : 20,' . "\n";
        $pageLine = '            isset($payload[\'page\']) ? (int) $payload[\'page\'] : 1,' . "\n";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\DTOs;

final readonly class {$entity}QueryData
{
    public function __construct(
        public ?int \$tenantId,
        public int \$perPage,
        public int \$page,
    ) {
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public static function fromArray(array \$payload): self
    {
        return new self(
{$tenantLine}{$perPageLine}{$pageLine}
        );
    }
}
PHP;
    }

    /**
     * @param list<array{name:string,type:string,nullable:bool,default:bool}> $columns
     */
    private function buildValueDto(string $moduleName, string $entity, array $columns): string
    {
        $params = [];
        $from = [];

        foreach ($columns as $column) {
            $property = Str::camel($column['name']);
            $nullable = $column['nullable'] || $column['name'] !== 'id';
            $type = $this->toPhpType($column['type'], $nullable);
            $sourceExpr = '$payload[\'' . $column['name'] . '\'] ?? null';
            $params[] = '        public ' . $type . ' $' . $property . ',';
            $from[] = '            ' . $this->castExpression($column, $sourceExpr) . ',';
        }

        $paramsText = implode("\n", $params);
        $fromText = implode("\n", $from);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\DTOs;

final readonly class {$entity}ValueData
{
    public function __construct(
{$paramsText}
    ) {
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public static function fromArray(array \$payload): self
    {
        return new self(
{$fromText}
        );
    }
}
PHP;
    }

    private function buildRecordMapper(string $moduleName, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Support;

use Modules\\{$moduleName}\\Application\\DTOs\\{$entity}ValueData;
use Modules\\Core\\Application\\DTO\\DataRecord;

final class {$entity}RecordMapper
{
    public function toValueData(DataRecord \$record): {$entity}ValueData
    {
        /** @var array<string, mixed> \$payload */
        \$payload = \$record->toArray();

        return {$entity}ValueData::fromArray(\$payload);
    }
}
PHP;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function buildRepositoryInterface(string $moduleName, string $entity, array $table): string
    {
        $extraMethods = [];
        $lookupSpecs = $this->uniqueLookupSpecs($table);

        if ($table['tenantAware']) {
            $extraMethods[] = '    /** @return list<DataRecord> */';
            $extraMethods[] = '    public function listByTenant(int|string $tenantId): array;';
        }

        foreach ($lookupSpecs as $spec) {
            $extraMethods[] = '    public function findBy' . $spec['studly'] . '(string $value): ?DataRecord;';

            if ($table['tenantAware']) {
                $extraMethods[] = '    public function findByTenantAnd' . $spec['studly'] . '(int|string $tenantId, string $value): ?DataRecord;';
            }
        }

        $methodsText = implode("\n", $extraMethods);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Repositories;

use Modules\\Core\\Application\\DTO\\DataRecord;
use Modules\\Core\\Application\\Repositories\\Contracts\\RepositoryPortInterface;

interface {$entity}RepositoryInterface extends RepositoryPortInterface
{
{$methodsText}
}
PHP;
    }

    private function buildListContract(string $moduleName, string $plural): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural};

use Modules\\Core\\Application\\Results\\Result;

interface List{$plural}ServiceInterface
{
    /**
     * @param array<string, mixed> \$criteria
     */
    public function execute(array \$criteria, int \$perPage, int \$page): Result;
}
PHP;
    }

    private function buildGetContract(string $moduleName, string $plural, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural};

use Modules\\Core\\Application\\Results\\Result;

interface Get{$entity}ServiceInterface
{
    public function execute(int|string \$id): Result;
}
PHP;
    }

    private function buildCreateContract(string $moduleName, string $plural, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural};

use Modules\\Core\\Application\\Results\\Result;

interface Create{$entity}ServiceInterface
{
    /**
     * @param array<string, mixed> \$payload
     */
    public function execute(array \$payload): Result;
}
PHP;
    }

    private function buildUpdateContract(string $moduleName, string $plural, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural};

use Modules\\Core\\Application\\Results\\Result;

interface Update{$entity}ServiceInterface
{
    /**
     * @param array<string, mixed> \$payload
     */
    public function execute(int|string \$id, array \$payload): Result;
}
PHP;
    }

    private function buildDeleteContract(string $moduleName, string $plural, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural};

use Modules\\Core\\Application\\Results\\Result;

interface Delete{$entity}ServiceInterface
{
    public function execute(int|string \$id): Result;
}
PHP;
    }

    private function buildListService(string $moduleName, string $entity, string $plural, bool $tenantAware): string
    {
        $repo = "{$entity}RepositoryInterface";
        $error = "{$moduleName}ErrorCode";
        $tenantCriteria = $tenantAware
            ? "\n            if (! array_key_exists('tenant_id', \$criteria)) {\n"
                . "                return Result::failure(new Error({$error}::INVALID_VALUE, 'tenant_id is required.'));\n"
                . "            }\n"
            : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\UseCases\\{$plural};

use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\List{$plural}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Repositories\\{$repo};
use Modules\\{$moduleName}\\Domain\\Constants\\{$error};
use Modules\\Core\\Application\\Results\\Error;
use Modules\\Core\\Application\\Results\\Result;
use Throwable;

final class List{$plural}Service implements List{$plural}ServiceInterface
{
    public function __construct(private readonly {$repo} \$records)
    {
    }

    /**
     * @param array<string, mixed> \$criteria
     */
    public function execute(array \$criteria, int \$perPage, int \$page): Result
    {
        try {{$tenantCriteria}
            return Result::success(\$this->records->page(\$criteria, \$perPage, \$page));
        } catch (Throwable \$exception) {
            return Result::failure(new Error({$error}::INVALID_VALUE, \$exception->getMessage()));
        }
    }
}
PHP;
    }

    private function buildGetService(string $moduleName, string $entity, string $plural, bool $tenantAware): string
    {
        $repo = "{$entity}RepositoryInterface";
        $error = "{$moduleName}ErrorCode";
        $tenantGuard = $tenantAware
            ? "\n"
                . '            $authenticatedTenantId = (int) (auth()->user()?->tenant_id ?? 0);' . "\n"
                . '            $recordTenantId = (int) $record->get(\'tenant_id\', 0);' . "\n\n"
                . '            if ($authenticatedTenantId > 0 && $recordTenantId > 0 && $authenticatedTenantId !== $recordTenantId) {' . "\n"
                . "                return Result::failure(new Error({$error}::INVALID_VALUE, 'Tenant scope mismatch.'));\n"
                . "            }\n"
            : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\UseCases\\{$plural};

use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Get{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Repositories\\{$repo};
use Modules\\{$moduleName}\\Domain\\Constants\\{$error};
use Modules\\Core\\Application\\Results\\Error;
use Modules\\Core\\Application\\Results\\Result;
use Throwable;

final class Get{$entity}Service implements Get{$entity}ServiceInterface
{
    public function __construct(private readonly {$repo} \$records)
    {
    }

    public function execute(int|string \$id): Result
    {
        try {
            \$record = \$this->records->findById(\$id);
            if (\$record === null) {
                return Result::failure(new Error({$error}::NOT_FOUND, '{$entity} not found.'));
            }
{$tenantGuard}

            return Result::success(\$record);
        } catch (Throwable \$exception) {
            return Result::failure(new Error({$error}::INVALID_VALUE, \$exception->getMessage()));
        }
    }
}
PHP;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function buildCreateService(string $moduleName, string $entity, string $plural, array $table): string
    {
        $repo = "{$entity}RepositoryInterface";
        $error = "{$moduleName}ErrorCode";
        $uniqueChecks = $this->buildCreateUniqueChecks($table, $entity, $error);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\UseCases\\{$plural};

use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Create{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Repositories\\{$repo};
use Modules\\{$moduleName}\\Domain\\Constants\\{$error};
use Modules\\Core\\Application\\Results\\Error;
use Modules\\Core\\Application\\Results\\Result;
use Throwable;

final class Create{$entity}Service implements Create{$entity}ServiceInterface
{
    public function __construct(private readonly {$repo} \$records)
    {
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public function execute(array \$payload): Result
    {
        try {
{$uniqueChecks}

            if (! array_key_exists('row_version', \$payload)) {
                \$payload['row_version'] = 1;
            }

            return Result::success(\$this->records->create(\$payload));
        } catch (Throwable \$exception) {
            return Result::failure(new Error({$error}::INVALID_VALUE, \$exception->getMessage()));
        }
    }
}
PHP;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function buildUpdateService(string $moduleName, string $entity, string $plural, array $table): string
    {
        $repo = "{$entity}RepositoryInterface";
        $error = "{$moduleName}ErrorCode";
        $tenantAware = $table['tenantAware'];
        $tenantGuard = $tenantAware
            ? "\n"
                . "            if (array_key_exists('tenant_id', \$payload)) {\n"
                . '                $requestedTenantId = (int) $payload[\'tenant_id\'];' . "\n"
                . '                $existingTenantId = (int) $existing->get(\'tenant_id\', 0);' . "\n\n"
                . '                if ($requestedTenantId > 0 && $existingTenantId > 0 && $requestedTenantId !== $existingTenantId) {' . "\n"
                . "                    return Result::failure(new Error({$error}::INVALID_VALUE, 'Tenant scope mismatch.'));\n"
                . "                }\n"
                . "            }\n"
                . "\n"
                . '            $authenticatedTenantId = (int) (auth()->user()?->tenant_id ?? 0);' . "\n"
                . '            $existingTenantId = (int) $existing->get(\'tenant_id\', 0);' . "\n\n"
                . '            if ($authenticatedTenantId > 0 && $existingTenantId > 0 && $authenticatedTenantId !== $existingTenantId) {' . "\n"
                . "                return Result::failure(new Error({$error}::INVALID_VALUE, 'Tenant scope mismatch.'));\n"
                . "            }\n"
            : '';
            $uniqueChecks = $this->buildUpdateUniqueChecks($table, $entity, $error);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\UseCases\\{$plural};

use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Update{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Repositories\\{$repo};
use Modules\\{$moduleName}\\Domain\\Constants\\{$error};
use Modules\\Core\\Application\\Results\\Error;
use Modules\\Core\\Application\\Results\\Result;
use Throwable;

final class Update{$entity}Service implements Update{$entity}ServiceInterface
{
    public function __construct(private readonly {$repo} \$records)
    {
    }

    /**
     * @param array<string, mixed> \$payload
     */
    public function execute(int|string \$id, array \$payload): Result
    {
        try {
            \$existing = \$this->records->findById(\$id);
            if (\$existing === null) {
                return Result::failure(new Error({$error}::NOT_FOUND, '{$entity} not found.'));
            }
{$tenantGuard}
{$uniqueChecks}

            if (! array_key_exists('row_version', \$payload)) {
                \$payload['row_version'] = ((int) \$existing->get('row_version', 1)) + 1;
            }

            return Result::success(\$this->records->update(\$id, \$payload));
        } catch (Throwable \$exception) {
            return Result::failure(new Error({$error}::INVALID_VALUE, \$exception->getMessage()));
        }
    }
}
PHP;
    }

    private function buildDeleteService(string $moduleName, string $entity, string $plural, bool $tenantAware): string
    {
        $repo = "{$entity}RepositoryInterface";
        $error = "{$moduleName}ErrorCode";
        $tenantGuard = $tenantAware
            ? "\n"
                . '            $authenticatedTenantId = (int) (auth()->user()?->tenant_id ?? 0);' . "\n"
                . '            $recordTenantId = (int) $record->get(\'tenant_id\', 0);' . "\n\n"
                . '            if ($authenticatedTenantId > 0 && $recordTenantId > 0 && $authenticatedTenantId !== $recordTenantId) {' . "\n"
                . "                return Result::failure(new Error({$error}::INVALID_VALUE, 'Tenant scope mismatch.'));\n"
                . "            }\n"
            : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Application\\UseCases\\{$plural};

use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Delete{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Repositories\\{$repo};
use Modules\\{$moduleName}\\Domain\\Constants\\{$error};
use Modules\\Core\\Application\\Results\\Error;
use Modules\\Core\\Application\\Results\\Result;
use Throwable;

final class Delete{$entity}Service implements Delete{$entity}ServiceInterface
{
    public function __construct(private readonly {$repo} \$records)
    {
    }

    public function execute(int|string \$id): Result
    {
        try {
            \$record = \$this->records->findById(\$id);
            if (\$record === null) {
                return Result::failure(new Error({$error}::NOT_FOUND, '{$entity} not found.'));
            }
{$tenantGuard}

            return Result::success(\$this->records->delete(\$id));
        } catch (Throwable \$exception) {
            return Result::failure(new Error({$error}::INVALID_VALUE, \$exception->getMessage()));
        }
    }
}
PHP;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     * @param list<array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * }> $moduleTables
     */
    private function buildModel(string $moduleName, array $table, array $moduleTables): string
    {
        $entity = $table['entity'];
        $relationUses = [];
        $relationMethods = [];

        foreach ($table['columns'] as $column) {
            $name = $column['name'];
            $isForeignLike = $column['type'] === 'foreignId' || str_ends_with($name, '_id');

            if (! $isForeignLike || in_array($name, ['id', 'row_version'], true)) {
                continue;
            }

            $relationName = Str::camel(Str::replaceLast('_id', '', $name));
            $relatedClass = $this->resolveRelatedModelClass($moduleName, $entity, $name);

            if ($relatedClass === null) {
                continue;
            }

            $relationUses['BelongsTo'] = "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;";
            $relationMethods[] = "\n    public function {$relationName}(): BelongsTo\n"
                . "    {\n"
                . '        return $this->belongsTo(' . $relatedClass . "::class, '{$name}');\n"
                . "    }\n";
        }

        $foreignKeyName = Str::snake($entity) . '_id';
        foreach ($moduleTables as $candidate) {
            if ($candidate['entity'] === $entity) {
                continue;
            }

            $hasForeignKey = collect($candidate['columns'])->contains(
                fn (array $column): bool => $column['name'] === $foreignKeyName,
            );

            if (! $hasForeignKey) {
                continue;
            }

            $relationUses['HasMany'] = "use Illuminate\\Database\\Eloquent\\Relations\\HasMany;";
            $methodName = Str::camel($candidate['plural']);
            $relatedClass = "\\Modules\\{$moduleName}\\Infrastructure\\Persistence\\Eloquent\\Models\\{$candidate['entity']}Model";

            $relationMethods[] = "\n    public function {$methodName}(): HasMany\n"
                . "    {\n"
                . '        return $this->hasMany(' . $relatedClass . "::class, '{$foreignKeyName}');\n"
                . "    }\n";
        }

        $softDeletesUse = $table['softDeletes'] ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '';
        $softDeletesTrait = $table['softDeletes'] ? "\n    use SoftDeletes;\n" : "\n";

        $casts = [];
        foreach ($table['columns'] as $column) {
            $cast = $this->toCast($column['type']);
            if ($cast === null) {
                continue;
            }

            if (in_array($column['name'], ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $casts[] = "            '{$column['name']}' => '{$cast}',";
        }

        $castsBlock = '';
        if ($casts !== []) {
            $castsBlock = "\n    protected function casts(): array\n    {\n"
                . "        return array_merge(parent::casts(), [\n"
                . implode("\n", $casts)
                . "\n        ]);\n    }\n";
        }

        $relationMethodsText = implode("", $relationMethods);
    $relationsUse = $relationUses === [] ? '' : implode("\n", array_values($relationUses)) . "\n";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Infrastructure\\Persistence\\Eloquent\\Models;

{$relationsUse}{$softDeletesUse}use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Models\\CoreModel;

final class {$entity}Model extends CoreModel
{{$softDeletesTrait}
    protected \$table = '{$table['table']}';

    protected \$guarded = ['id'];{$castsBlock}{$relationMethodsText}}
PHP;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function buildEloquentRepository(string $moduleName, string $entity, array $table): string
    {
        $extraImports = [
            'use Illuminate\\Database\\Eloquent\\Model;',
            'use Modules\\Core\\Application\\DTO\\DataRecord;',
        ];
        $extraMethods = [];
        $lookupSpecs = $this->uniqueLookupSpecs($table);

        if ($table['tenantAware']) {
            $extraMethods[] = "\n    /** @return list<DataRecord> */\n"
                . '    public function listByTenant(int|string $tenantId): array' . "\n"
                . "    {\n"
                . '        return $this->list([\'tenant_id\' => $tenantId]);' . "\n"
                . "    }\n";
        }

        foreach ($lookupSpecs as $spec) {
            $column = $spec['column'];
            $studly = $spec['studly'];

            $extraMethods[] = "\n"
                . '    public function findBy' . $studly . '(string $value): ?DataRecord' . "\n"
                . "    {\n"
                . '        $model = $this->query()->where(\'' . $column . '\', trim($value))->first();' . "\n\n"
                . '        if (! $model instanceof Model) {' . "\n"
                . "            return null;\n"
                . "        }\n\n"
                . '        return $this->toRecord($model);' . "\n"
                . "    }\n";

            if ($table['tenantAware']) {
                $extraMethods[] = "\n"
                    . '    public function findByTenantAnd' . $studly . '(int|string $tenantId, string $value): ?DataRecord' . "\n"
                    . "    {\n"
                    . '        $model = $this->query()' . "\n"
                    . '            ->where(\'tenant_id\', $tenantId)' . "\n"
                    . '            ->where(\'' . $column . '\', trim($value))' . "\n"
                    . '            ->first();' . "\n\n"
                    . '        if (! $model instanceof Model) {' . "\n"
                    . "            return null;\n"
                    . "        }\n\n"
                    . '        return $this->toRecord($model);' . "\n"
                    . "    }\n";
            }
        }

        $extraImportsText = implode("\n", $extraImports);
        $extraMethodsText = implode("", $extraMethods);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Infrastructure\\Persistence\\Eloquent\\Repositories;

{$extraImportsText}
use Modules\\{$moduleName}\\Application\\Repositories\\{$entity}RepositoryInterface;
use Modules\\{$moduleName}\\Infrastructure\\Persistence\\Eloquent\\Models\\{$entity}Model;
use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Repositories\\EloquentRepository;

final class Eloquent{$entity}Repository extends EloquentRepository implements {$entity}RepositoryInterface
{
    public function __construct({$entity}Model \$model)
    {
        parent::__construct(\$model);
    }{$extraMethodsText}
}
PHP;
    }

    private function resolveRelatedModelClass(string $moduleName, string $entity, string $columnName): ?string
    {
        $explicit = match ($columnName) {
            'tenant_id' => '\Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel',
            'organization_unit_id' => '\Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel',
            'currency_id' => '\Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel',
            'user_id', 'created_by', 'updated_by' => '\Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel',
            default => null,
        };

        if ($explicit !== null) {
            return $explicit;
        }

        $stem = Str::replaceLast('_id', '', $columnName);

        if ($stem === 'parent') {
            return '\\Modules\\' . $moduleName . '\\Infrastructure\\Persistence\\Eloquent\\Models\\' . $entity . 'Model';
        }

        if (str_ends_with($stem, 'able')) {
            return null;
        }

        if (
            str_ends_with($stem, '_by')
            || in_array($stem, ['author', 'approver', 'reviewer', 'requested_by', 'performed_by', 'inspected_by', 'counted_by', 'completed_by', 'approved_by'], true)
        ) {
            return '\\Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserModel';
        }

        $baseName = Str::studly($stem) . 'Model';
        $matches = $this->findModelMatches($baseName);

        $preferred = $this->pickPreferredModelMatch($moduleName, $matches);
        if ($preferred !== null) {
            return $preferred;
        }

        if ($matches === [] && str_contains($stem, '_')) {
            $parts = explode('_', $stem);
            while (count($parts) > 1 && $matches === []) {
                array_shift($parts);
                $fallbackBaseName = Str::studly(implode('_', $parts)) . 'Model';
                $matches = $this->findModelMatches($fallbackBaseName);

                $preferred = $this->pickPreferredModelMatch($moduleName, $matches);
                if ($preferred !== null) {
                    return $preferred;
                }
            }
        }

        if ($matches === []) {
            $suffixMatches = $this->findModelMatchesBySuffix($baseName);
            $preferred = $this->pickPreferredModelMatch($moduleName, $suffixMatches);
            if ($preferred !== null) {
                return $preferred;
            }
        }

        return null;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     * @return list<array{column:string,studly:string}>
     */
    private function uniqueLookupSpecs(array $table): array
    {
        $names = array_map(static fn (array $column): string => $column['name'], $table['columns']);
        $targets = ['code', 'key', 'name', 'slug'];
        $specs = [];

        foreach ($targets as $target) {
            if (! in_array($target, $names, true)) {
                continue;
            }

            $specs[] = [
                'column' => $target,
                'studly' => Str::studly($target),
            ];
        }

        return $specs;
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function buildCreateUniqueChecks(array $table, string $entity, string $error): string
    {
        $checks = [];

        foreach ($this->uniqueLookupSpecs($table) as $spec) {
            $column = $spec['column'];
            $studly = $spec['studly'];

            if ($table['tenantAware']) {
                $checks[] = '            if (array_key_exists(\'tenant_id\', $payload) && array_key_exists(\'' . $column . '\', $payload)) {' . "\n"
                    . '                $tenantId = (int) $payload[\'tenant_id\'];' . "\n"
                    . '                $uniqueValue = trim((string) $payload[\'' . $column . '\']);' . "\n\n"
                    . '                if ($tenantId > 0 && $uniqueValue !== \'\') {' . "\n"
                    . '                    $byUnique = $this->records->findByTenantAnd' . $studly . '($tenantId, $uniqueValue);' . "\n"
                    . '                    if ($byUnique !== null) {' . "\n"
                    . '                        return Result::failure(new Error(' . $error . '::CONFLICT, \'' . $entity . ' ' . $column . ' already exists for tenant.\'));' . "\n"
                    . '                    }' . "\n"
                    . '                }' . "\n"
                    . '            }';

                continue;
            }

            $checks[] = '            if (array_key_exists(\'' . $column . '\', $payload)) {' . "\n"
                . '                $uniqueValue = trim((string) $payload[\'' . $column . '\']);' . "\n"
                . '                if ($uniqueValue !== \'\' && $this->records->findBy' . $studly . '($uniqueValue) !== null) {' . "\n"
                . '                    return Result::failure(new Error(' . $error . '::CONFLICT, \'' . $entity . ' ' . $column . ' already exists.\'));' . "\n"
                . '                }' . "\n"
                . '            }';
        }

        return implode("\n\n", $checks);
    }

    /**
     * @param array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * } $table
     */
    private function buildUpdateUniqueChecks(array $table, string $entity, string $error): string
    {
        $checks = [];

        foreach ($this->uniqueLookupSpecs($table) as $spec) {
            $column = $spec['column'];
            $studly = $spec['studly'];

            if ($table['tenantAware']) {
                $checks[] = '            $uniqueValue = array_key_exists(\'' . $column . '\', $payload)' . "\n"
                    . '                ? trim((string) $payload[\'' . $column . '\'])' . "\n"
                    . '                : trim((string) $existing->get(\'' . $column . '\', \'\'));' . "\n"
                    . '            if ($uniqueValue !== \'\') {' . "\n"
                    . '                $tenantId = (int) $existing->get(\'tenant_id\', 0);' . "\n"
                    . '                if ($tenantId > 0) {' . "\n"
                    . '                    $byUnique = $this->records->findByTenantAnd' . $studly . '($tenantId, $uniqueValue);' . "\n"
                    . '                    if ($byUnique !== null && (string) $byUnique->id() !== (string) $existing->id()) {' . "\n"
                    . '                        return Result::failure(new Error(' . $error . '::CONFLICT, \'' . $entity . ' ' . $column . ' already exists for tenant.\'));' . "\n"
                    . '                    }' . "\n"
                    . '                }' . "\n"
                    . '            }';

                continue;
            }

            $checks[] = '            $uniqueValue = array_key_exists(\'' . $column . '\', $payload)' . "\n"
                . '                ? trim((string) $payload[\'' . $column . '\'])' . "\n"
                . '                : trim((string) $existing->get(\'' . $column . '\', \'\'));' . "\n"
                . '            if ($uniqueValue !== \'\') {' . "\n"
                . '                $byUnique = $this->records->findBy' . $studly . '($uniqueValue);' . "\n"
                . '                if ($byUnique !== null && (string) $byUnique->id() !== (string) $existing->id()) {' . "\n"
                . '                    return Result::failure(new Error(' . $error . '::CONFLICT, \'' . $entity . ' ' . $column . ' already exists.\'));' . "\n"
                . '                }' . "\n"
                . '            }';
        }

        return implode("\n\n", $checks);
    }

    /** @return list<string> */
    private function findModelMatches(string $baseName): array
    {
        $pattern = base_path('app/Modules/*/Infrastructure/Persistence/Eloquent/Models/' . $baseName . '.php');

        return glob(str_replace('\\\\', '/', $pattern)) ?: [];
    }

    /** @return list<string> */
    private function findModelMatchesBySuffix(string $suffixBaseName): array
    {
        $pattern = base_path('app/Modules/*/Infrastructure/Persistence/Eloquent/Models/*' . $suffixBaseName . '.php');

        return glob(str_replace('\\\\', '/', $pattern)) ?: [];
    }

    /** @param list<string> $matches */
    private function pickPreferredModelMatch(string $moduleName, array $matches): ?string
    {
        if ($matches === []) {
            return null;
        }

        if (count($matches) === 1) {
            return $this->toModelNamespace($matches[0]);
        }

        $withinModule = array_values(array_filter(
            $matches,
            fn (string $match): bool => str_contains(str_replace('\\\\', '/', $match), '/Modules/' . $moduleName . '/'),
        ));

        if (count($withinModule) === 1) {
            return $this->toModelNamespace($withinModule[0]);
        }

        return null;
    }

    private function toModelNamespace(string $absolutePath): string
    {
        $normalized = str_replace('\\\\', '/', $absolutePath);
        $appPrefix = str_replace('\\\\', '/', base_path('app/'));
        $relative = Str::after($normalized, $appPrefix);

        return '\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
    }

    private function buildListRequest(string $moduleName, string $entity, bool $tenantAware): string
    {
        $tenantRule = $tenantAware
            ? "\n            'tenant_id' => ['required', 'integer', 'min:1'],"
            : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Presentation\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

final class List{$entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [{$tenantRule}
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
PHP;
    }

    /**
     * @param list<array{name:string,type:string,nullable:bool,default:bool}> $columns
     */
    private function buildUpsertRequest(string $moduleName, string $entity, array $columns): string
    {
        $rules = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            if ($name === 'row_version') {
                $rules[] = "            'row_version' => ['sometimes', 'integer', 'min:1'],";
                continue;
            }

            $ruleParts = [];
            $isRequiredOnPost = ! $column['nullable'] && ! $column['default'];
            $ruleParts[] = $isRequiredOnPost ? "\$this->isMethod('post') ? 'required' : 'sometimes'" : "'sometimes'";
            $ruleParts = array_merge($ruleParts, $this->validationRuleParts($column));

            $rules[] = "            '{$name}' => [" . implode(', ', $ruleParts) . '],';
        }

        $rulesText = implode("\n", $rules);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Presentation\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

final class Upsert{$entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
{$rulesText}
        ];
    }
}
PHP;
    }

    private function buildResource(string $moduleName, string $entity): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Presentation\\Http\\Resources;

final class {$entity}Resource extends DataRecordResource
{
}
PHP;
    }

    private function buildController(string $moduleName, string $entity, string $plural): string
    {
        $resourceCollection = "{$entity}Resource::collection(\$page->items)->resolve()";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Presentation\\Http\\Controllers;

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Routing\\Controller;
use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Create{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Delete{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Get{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\List{$plural}ServiceInterface;
use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\Update{$entity}ServiceInterface;
use Modules\\{$moduleName}\\Presentation\\Http\\Requests\\List{$entity}Request;
use Modules\\{$moduleName}\\Presentation\\Http\\Requests\\Upsert{$entity}Request;
use Modules\\{$moduleName}\\Presentation\\Http\\Resources\\{$entity}Resource;
use Modules\\Core\\Application\\DTO\\PagedResult;

final class {$entity}Controller extends Controller
{
    public function __construct(
        private readonly List{$plural}ServiceInterface \$listService,
        private readonly Get{$entity}ServiceInterface \$getService,
        private readonly Create{$entity}ServiceInterface \$createService,
        private readonly Update{$entity}ServiceInterface \$updateService,
        private readonly Delete{$entity}ServiceInterface \$deleteService,
    ) {
    }

    public function index(List{$entity}Request \$request): JsonResponse
    {
        \$validated = \$request->validated();
        \$criteria = \$validated;
        unset(\$criteria['per_page'], \$criteria['page']);

        \$result = \$this->listService->execute(
            \$criteria,
            (int) (\$validated['per_page'] ?? 20),
            (int) (\$validated['page'] ?? 1),
        );

        if (\$result->isFailure()) {
            return response()->json(['message' => \$result->errorOrFail()->message], 422);
        }

        \$page = \$result->valueOrFail();
        if (! \$page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => {$resourceCollection},
            'meta' => [
                'total' => \$page->total,
                'page' => \$page->page,
                'per_page' => \$page->perPage,
                'page_count' => \$page->pageCount(),
                'has_more' => \$page->hasMore(),
            ],
        ]);
    }

    public function show(int|string \$id): JsonResponse|{$entity}Resource
    {
        \$result = \$this->getService->execute(\$id);

        if (\$result->isFailure()) {
            return response()->json(['message' => \$result->errorOrFail()->message], 404);
        }

        return new {$entity}Resource(\$result->valueOrFail());
    }

    public function store(Upsert{$entity}Request \$request): JsonResponse|{$entity}Resource
    {
        \$result = \$this->createService->execute(\$request->validated());

        if (\$result->isFailure()) {
            return response()->json(['message' => \$result->errorOrFail()->message], 422);
        }

        return (new {$entity}Resource(\$result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(Upsert{$entity}Request \$request, int|string \$id): JsonResponse|{$entity}Resource
    {
        \$result = \$this->updateService->execute(\$id, \$request->validated());

        if (\$result->isFailure()) {
            \$error = \$result->errorOrFail();
            \$status = \$error->code === 'NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => \$error->message], \$status);
        }

        return new {$entity}Resource(\$result->valueOrFail());
    }

    public function destroy(int|string \$id): JsonResponse
    {
        \$result = \$this->deleteService->execute(\$id);

        if (\$result->isFailure()) {
            return response()->json(['message' => \$result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
PHP;
    }

    /**
     * @param list<array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * }> $tables
     */
    private function buildRoutesFile(string $moduleName, array $tables): string
    {
        $uses = [];
        $routes = [];

        foreach ($tables as $table) {
            $entity = $table['entity'];
            $uses[] = "use Modules\\{$moduleName}\\Presentation\\Http\\Controllers\\{$entity}Controller;";
            $routes[] = "        Route::apiResource('{$table['route']}', {$entity}Controller::class);";
        }

        $usesText = implode("\n", $uses);
        $routesText = implode("\n", $routes);
        $prefix = Str::lower($moduleName);

        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\\Support\\Facades\\Route;
{$usesText}

Route::prefix('api/{$prefix}')
    ->middleware('api')
    ->name('{$prefix}.')
    ->group(function (): void {
{$routesText}
    });
PHP;
    }

    /**
     * @param list<array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * }> $tables
     */
    private function buildServiceProvider(string $moduleName, array $tables): string
    {
        $imports = [
            'use Illuminate\\Support\\ServiceProvider;',
        ];

        $serviceBindings = [];
        $repoBindings = [];

        foreach ($tables as $table) {
            $entity = $table['entity'];
            $plural = $table['plural'];

            foreach (['List' . $plural, 'Get' . $entity, 'Create' . $entity, 'Update' . $entity, 'Delete' . $entity] as $service) {
                $imports[] = "use Modules\\{$moduleName}\\Application\\Contracts\\UseCases\\{$plural}\\{$service}ServiceInterface;";
                $imports[] = "use Modules\\{$moduleName}\\Application\\UseCases\\{$plural}\\{$service}Service;";
                $serviceBindings[] = "                {$service}ServiceInterface::class => {$service}Service::class,";
            }

            $imports[] = "use Modules\\{$moduleName}\\Application\\Repositories\\{$entity}RepositoryInterface;";
            $imports[] = "use Modules\\{$moduleName}\\Infrastructure\\Persistence\\Eloquent\\Models\\{$entity}Model;";
            $imports[] = "use Modules\\{$moduleName}\\Infrastructure\\Persistence\\Eloquent\\Repositories\\Eloquent{$entity}Repository;";
            $repoBindings[] = "        \$this->app->singleton({$entity}RepositoryInterface::class, function (): {$entity}RepositoryInterface {";
            $repoBindings[] = "            return new Eloquent{$entity}Repository(new {$entity}Model());";
            $repoBindings[] = '        });';
            $repoBindings[] = '';
        }

        $imports = array_values(array_unique($imports));
        sort($imports);
        $importsText = implode("\n", $imports);
        $serviceText = implode("\n", $serviceBindings);
        $repoText = rtrim(implode("\n", $repoBindings));
        $configKey = Str::snake($moduleName);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Infrastructure\\Providers;

{$importsText}

final class {$moduleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$this->mergeConfigFrom(__DIR__ . '/../Config/{$configKey}.php', '{$configKey}');

        foreach (
            [
{$serviceText}
            ] as \$contract => \$implementation
        ) {
            \$this->app->singleton(\$contract, \$implementation);
        }

{$repoText}
    }

    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        \$this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
PHP;
    }

    /**
     * @param list<array{
     *   table:string,
     *   entity:string,
     *   plural:string,
     *   route:string,
     *   softDeletes:bool,
     *   tenantAware:bool,
     *   columns:list<array{name:string,type:string,nullable:bool,default:bool}>
     * }> $tables
     */
    private function buildReadme(string $moduleName, array $tables): string
    {
        $lines = ["# {$moduleName} Module", '', 'Generated from migration tables:', ''];

        foreach ($tables as $table) {
            $lines[] = "- `{$table['table']}` -> `{$table['entity']}`";
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<string> $providerClasses
     */
    private function syncBootstrapProviders(array $providerClasses): void
    {
        $path = base_path('bootstrap/providers.php');
        if (! is_file($path)) {
            return;
        }

        $content = (string) file_get_contents($path);
        if ($content === '') {
            return;
        }

        $existing = [];
        if (preg_match_all('/^\s*([A-Za-z0-9_\\\\]+::class),?\s*$/m', $content, $matches)) {
            $existing = $matches[1];
        }

        $toAdd = [];
        foreach ($providerClasses as $providerClass) {
            if (! in_array($providerClass, $existing, true)) {
                $toAdd[] = $providerClass;
            }
        }

        if ($toAdd === []) {
            return;
        }

        $content = preg_replace('/(::class)\s*\n\];\s*$/m', '$1,' . "\n];\n", $content) ?? $content;

        $insert = '';
        foreach ($toAdd as $providerClass) {
            $insert .= "    {$providerClass},\n";
        }

        $updated = preg_replace('/\n\];\s*$/', "\n{$insert}];\n", $content);
        if (is_string($updated) && $updated !== '') {
            file_put_contents($path, $updated);
            $this->info('Synchronized bootstrap/providers.php with generated module providers.');
        }
    }

    private function toPhpType(string $migrationType, bool $nullable): string
    {
        $base = match ($migrationType) {
            'id', 'foreignId', 'unsignedBigInteger', 'bigInteger', 'integer', 'unsignedInteger',
            'smallInteger', 'tinyInteger' => 'int',
            'boolean' => 'bool',
            'decimal', 'double', 'float' => 'float',
            'json' => 'array',
            default => 'string',
        };

        return $nullable ? '?' . $base : $base;
    }

    /**
     * @param array{name:string,type:string,nullable:bool,default:bool} $column
     */
    private function castExpression(array $column, string $valueExpr): string
    {
        $type = $column['type'];
        $nullable = $column['nullable'] || $column['name'] !== 'id';

        $caster = match ($type) {
            'id', 'foreignId', 'unsignedBigInteger', 'bigInteger', 'integer', 'unsignedInteger',
            'smallInteger', 'tinyInteger' => '(int) ',
            'boolean' => '(bool) ',
            'decimal', 'double', 'float' => '(float) ',
            'json' => '(array) ',
            default => '(string) ',
        };

        if (! $nullable) {
            return $caster . "({$valueExpr} ?? 0)";
        }

        return "{$valueExpr} !== null ? {$caster}{$valueExpr} : null";
    }

    private function toCast(string $migrationType): ?string
    {
        return match ($migrationType) {
            'foreignId', 'unsignedBigInteger', 'bigInteger', 'integer', 'unsignedInteger',
            'smallInteger', 'tinyInteger' => 'integer',
            'boolean' => 'boolean',
            'json' => 'array',
            'decimal' => 'decimal:4',
            'timestamp', 'timestampTz', 'date', 'dateTime', 'dateTimeTz' => 'datetime',
            default => null,
        };
    }

    /**
     * @param array{name:string,type:string,nullable:bool,default:bool} $column
     * @return list<string>
     */
    private function validationRuleParts(array $column): array
    {
        $rules = [];

        if ($column['nullable']) {
            $rules[] = "'nullable'";
        }

        $typeRules = match ($column['type']) {
            'id', 'foreignId', 'unsignedBigInteger', 'bigInteger', 'integer', 'unsignedInteger',
            'smallInteger', 'tinyInteger'
                => ["'integer'", "'min:1'"],
            'boolean' => ["'boolean'"],
            'decimal', 'double', 'float' => ["'numeric'"],
            'json' => ["'array'"],
            'text', 'longText', 'mediumText' => ["'string'"],
            'timestamp', 'timestampTz', 'date', 'dateTime', 'dateTimeTz' => ["'date'"],
            default => ["'string'", "'max:255'"],
        };

        return array_merge($rules, $typeRules);
    }
}


