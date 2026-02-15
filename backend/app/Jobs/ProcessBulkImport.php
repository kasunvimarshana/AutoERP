<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBulkImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly string $importType,
        private readonly string $filePath,
        private readonly array $mapping
    ) {}

    public function handle(): void
    {
        Log::info("Starting bulk import", [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'import_type' => $this->importType,
            'file_path' => $this->filePath,
        ]);

        DB::transaction(function () {
            $rows = $this->readCsvFile($this->filePath);
            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $this->importRow($row, $index);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Import row failed", [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("Bulk import completed", [
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ]);

            // Create import log record
            DB::table('import_logs')->insert([
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'import_type' => $this->importType,
                'file_path' => $this->filePath,
                'total_rows' => count($rows),
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'errors' => json_encode($errors),
                'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function readCsvFile(string $filePath): array
    {
        $rows = [];
        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            $rows[] = array_combine($headers, $data);
        }

        fclose($file);
        return $rows;
    }

    private function importRow(array $row, int $index): void
    {
        // Map row data according to mapping configuration
        $data = [];
        foreach ($this->mapping as $targetField => $sourceField) {
            $data[$targetField] = $row[$sourceField] ?? null;
        }

        // Add tenant context
        $data['tenant_id'] = $this->tenantId;
        $data['created_by'] = $this->userId;

        // Import based on type
        match ($this->importType) {
            'products' => $this->importProduct($data),
            'customers' => $this->importCustomer($data),
            'vendors' => $this->importVendor($data),
            'inventory' => $this->importInventory($data),
            default => throw new \InvalidArgumentException("Unknown import type: {$this->importType}"),
        };
    }

    private function importProduct(array $data): void
    {
        \App\Modules\Product\Models\Product::create($data);
    }

    private function importCustomer(array $data): void
    {
        \App\Modules\CRM\Models\Customer::create($data);
    }

    private function importVendor(array $data): void
    {
        \App\Modules\Procurement\Models\Vendor::create($data);
    }

    private function importInventory(array $data): void
    {
        \App\Modules\Inventory\Models\StockLedger::create($data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Bulk import job failed", [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'import_type' => $this->importType,
            'error' => $exception->getMessage(),
        ]);

        // Update import log
        DB::table('import_logs')->insert([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'import_type' => $this->importType,
            'file_path' => $this->filePath,
            'status' => 'failed',
            'errors' => json_encode(['error' => $exception->getMessage()]),
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
