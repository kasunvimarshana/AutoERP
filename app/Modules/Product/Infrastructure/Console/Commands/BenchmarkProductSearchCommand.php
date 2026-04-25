<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;

class BenchmarkProductSearchCommand extends Command
{
    protected $signature = 'product:benchmark-search
        {--tenant_id= : Tenant id to benchmark against}
        {--warehouse_id= : Warehouse id for stock-scoped benchmarks}
        {--term=* : Search terms (can be provided multiple times)}
        {--iterations=3 : Number of iterations per term}
        {--per_page=25 : Per-page size for search execution}
        {--include_pricing=0 : Include pricing in benchmark run (0 or 1)}
        {--format=table : Output format: table or json}';

    protected $description = 'Benchmark Product catalog search latency for selected tenant and warehouse.';

    public function __construct(private readonly SearchProductCatalogServiceInterface $searchProductCatalogService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $format = strtolower(trim((string) $this->option('format')));
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('Invalid --format option. Allowed values: table, json.');

            return self::FAILURE;
        }

        if (! $this->canConnectToDatabase($format)) {
            return self::FAILURE;
        }

        $tenantId = $this->resolveTenantId();
        if ($tenantId === null) {
            if ($this->isJsonFormat($format)) {
                $this->emitJson([
                    'status' => 'no_data',
                    'message' => 'No tenant found for benchmarking.',
                ]);
            } else {
                $this->warn('No tenant found for benchmarking.');
            }

            return self::SUCCESS;
        }

        $warehouseId = $this->resolveWarehouseId($tenantId);
        if ($warehouseId === null) {
            $message = sprintf('No warehouse found for tenant %d.', $tenantId);
            if ($this->isJsonFormat($format)) {
                $this->emitJson([
                    'status' => 'no_data',
                    'message' => $message,
                    'tenant_id' => $tenantId,
                ]);
            } else {
                $this->warn($message);
            }

            return self::SUCCESS;
        }

        $iterations = max(1, (int) $this->option('iterations'));
        $perPage = max(1, min((int) $this->option('per_page'), 200));
        $includePricing = (bool) ((int) $this->option('include_pricing'));

        /** @var array<int, string> $terms */
        $terms = array_values(array_filter(array_map('trim', (array) $this->option('term')), static fn (string $term): bool => $term !== ''));
        if ($terms === []) {
            $terms = $this->resolveDefaultTerms($tenantId);
        }

        if ($terms === []) {
            if ($this->isJsonFormat($format)) {
                $this->emitJson([
                    'status' => 'no_data',
                    'message' => 'No search terms available. Provide --term options.',
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouseId,
                ]);
            } else {
                $this->warn('No search terms available. Provide --term options.');
            }

            return self::SUCCESS;
        }

        if (! $this->isJsonFormat($format)) {
            $this->info(sprintf('Benchmarking product search: tenant=%d warehouse=%d iterations=%d terms=%d', $tenantId, $warehouseId, $iterations, count($terms)));
        }

        try {
            // Warm-up execution before capturing timings.
            $this->searchProductCatalogService->execute([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'term' => $terms[0],
                'include_pricing' => $includePricing,
                'per_page' => $perPage,
                'page' => 1,
                'sort' => 'name:asc',
            ]);

            $rows = [];

            foreach ($terms as $term) {
                $timings = [];
                $resultCount = 0;

                for ($i = 0; $i < $iterations; $i++) {
                    $startedAt = microtime(true);

                    $payload = $this->searchProductCatalogService->execute([
                        'tenant_id' => $tenantId,
                        'warehouse_id' => $warehouseId,
                        'term' => $term,
                        'stock_status' => 'in_stock',
                        'include_pricing' => $includePricing,
                        'per_page' => $perPage,
                        'page' => 1,
                        'sort' => 'name:asc',
                    ]);

                    $timings[] = (microtime(true) - $startedAt) * 1000;
                    $resultCount = (int) ($payload['meta']['total'] ?? 0);
                }

                $rows[] = [
                    'term' => $term,
                    'matches' => $resultCount,
                    'min_ms' => round(min($timings), 2),
                    'avg_ms' => round(array_sum($timings) / count($timings), 2),
                    'p95_ms' => round($this->percentile($timings, 0.95), 2),
                    'max_ms' => round(max($timings), 2),
                ];
            }

            if ($this->isJsonFormat($format)) {
                $this->emitJson([
                    'status' => 'success',
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouseId,
                    'iterations' => $iterations,
                    'per_page' => $perPage,
                    'include_pricing' => $includePricing,
                    'terms' => $terms,
                    'results' => $rows,
                    'generated_at' => now()->toIso8601String(),
                ]);
            } else {
                $tableRows = array_map(function (array $row): array {
                    return [
                        'term' => $row['term'],
                        'matches' => $row['matches'],
                        'min_ms' => $this->formatMs((float) $row['min_ms']),
                        'avg_ms' => $this->formatMs((float) $row['avg_ms']),
                        'p95_ms' => $this->formatMs((float) $row['p95_ms']),
                        'max_ms' => $this->formatMs((float) $row['max_ms']),
                    ];
                }, $rows);

                $this->table(['term', 'matches', 'min_ms', 'avg_ms', 'p95_ms', 'max_ms'], $tableRows);
            }

            return self::SUCCESS;
        } catch (QueryException $exception) {
            if ($this->isJsonFormat($format)) {
                $this->emitJson([
                    'status' => 'error',
                    'message' => 'Benchmark failed due to a database query error.',
                    'error' => $exception->getMessage(),
                ]);
            } else {
                $this->error('Benchmark failed due to a database query error.');
                $this->line($exception->getMessage());
            }

            return self::FAILURE;
        }
    }

    private function canConnectToDatabase(string $format): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $exception) {
            if ($this->isJsonFormat($format)) {
                $this->emitJson([
                    'status' => 'error',
                    'message' => 'Unable to connect to the configured database for benchmarking.',
                    'error' => $exception->getMessage(),
                ]);
            } else {
                $this->error('Unable to connect to the configured database for benchmarking.');
                $this->line($exception->getMessage());
            }

            return false;
        }
    }

    private function isJsonFormat(string $format): bool
    {
        return $format === 'json';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function emitJson(array $payload): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $this->line($json !== false ? $json : '{"status":"error","message":"Failed to encode JSON output."}');
    }

    private function resolveTenantId(): ?int
    {
        $tenantOption = $this->option('tenant_id');
        if (is_string($tenantOption) && trim($tenantOption) !== '') {
            return (int) $tenantOption;
        }

        $tenantId = DB::table('products')->orderBy('tenant_id')->value('tenant_id');

        return $tenantId !== null ? (int) $tenantId : null;
    }

    private function resolveWarehouseId(int $tenantId): ?int
    {
        $warehouseOption = $this->option('warehouse_id');
        if (is_string($warehouseOption) && trim($warehouseOption) !== '') {
            return (int) $warehouseOption;
        }

        $warehouseId = DB::table('warehouse_locations')
            ->where('tenant_id', $tenantId)
            ->orderBy('warehouse_id')
            ->value('warehouse_id');

        return $warehouseId !== null ? (int) $warehouseId : null;
    }

    /**
     * @return array<int, string>
     */
    private function resolveDefaultTerms(int $tenantId): array
    {
        $sku = DB::table('products')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('sku')
            ->orderBy('id')
            ->value('sku');

        $identifier = DB::table('product_identifiers')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('value')
            ->orderBy('id')
            ->value('value');

        $name = DB::table('products')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('name')
            ->orderBy('id')
            ->value('name');

        return array_values(array_filter([
            is_string($identifier) ? trim($identifier) : '',
            is_string($sku) ? trim($sku) : '',
            is_string($name) ? trim($name) : '',
        ], static fn (string $term): bool => $term !== ''));
    }

    /**
     * @param  array<int, float>  $values
     */
    private function percentile(array $values, float $percentile): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $index = (int) ceil(($count * $percentile) - 1);
        $index = max(0, min($index, $count - 1));

        return $values[$index];
    }

    private function formatMs(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
