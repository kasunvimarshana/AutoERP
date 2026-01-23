<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSwaggerDocs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'swagger:generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate Swagger/OpenAPI documentation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating Swagger documentation...');

        $paths = [
            base_path('app/Http/Controllers'),
            base_path('Modules/Auth/app/Http/Controllers'),
            base_path('Modules/User/app/Http/Controllers'),
        ];

        $outputPath = storage_path('api-docs/api-docs.json');

        // Ensure directory exists
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Run openapi command
        $command = sprintf(
            '%s %s -o %s 2>&1',
            base_path('vendor/bin/openapi'),
            implode(' ', $paths),
            $outputPath
        );

        exec($command, $output, $returnCode);

        // Filter out warnings
        $filteredOutput = array_filter($output, function ($line) {
            return ! str_contains($line, 'Warning:');
        });

        if (! empty($filteredOutput)) {
            $this->line(implode("\n", $filteredOutput));
        }

        if (file_exists($outputPath)) {
            $this->info('Swagger documentation generated successfully!');
            $this->info("Output: {$outputPath}");

            return Command::SUCCESS;
        }

        $this->error('Failed to generate Swagger documentation');

        return Command::FAILURE;
    }
}
