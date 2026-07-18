<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class VehicleRentalRuntimeReferenceTest extends TestCase
{
    /** @var list<string> */
    private const RETIRED_TABLES = [
        'rental_usage_facts',
        'rental_calculation_sources',
        'rental_status_histories',
        'rental_deposit_links',
        'rental_deposit_requirements',
        'rental_calculation_lines',
        'rental_calculation_runs',
        'rental_billing_periods',
        'rental_expense_allocations',
        'rental_expenses',
        'rental_usage_contexts',
        'rental_usage_events',
        'rental_usage_logs',
        'rental_custody_event_items',
        'rental_custody_events',
        'rental_vehicle_replacements',
        'rental_driver_assignments',
        'rental_vehicle_allocations',
        'vehicle_finance_status_histories',
        'vehicle_finance_installments',
        'vehicle_finance_agreements',
        'rental_agreement_rate_components',
        'rental_agreement_rate_versions',
        'rental_agreement_terms',
        'rental_agreements',
        'rental_reservations',
    ];

    public function test_active_runtime_does_not_reference_retired_vehicle_rental_implementation(): void
    {
        foreach ($this->runtimeFiles() as $path) {
            $source = file_get_contents($path);
            self::assertNotFalse($source, $path);

            foreach ([
                'Modules\\VehicleRental',
                '/vehicle-rental',
                "'vehicle-rental'",
                '"vehicle-rental"',
            ] as $retiredReference) {
                self::assertStringNotContainsString($retiredReference, $source, $path);
            }

            foreach (self::RETIRED_TABLES as $table) {
                self::assertStringNotContainsString($table, $source, $path);
            }
        }
    }

    /** @return list<string> */
    private function runtimeFiles(): array
    {
        $files = [
            $this->projectPath('bootstrap/providers.php'),
            $this->projectPath('routes/console.php'),
        ];

        foreach (['app/Modules', 'resources/js'] as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->projectPath($directory)),
            );

            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                $path = str_replace('\\', '/', $file->getPathname());
                if (str_contains($path, '/Tests/') || str_contains($path, '.test.')) {
                    continue;
                }

                if (! in_array(strtolower($file->getExtension()), ['php', 'ts', 'tsx'], true)) {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        return array_values(array_unique($files));
    }

    private function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
