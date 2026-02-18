<?php

namespace App\Console\Commands;

use App\Services\ApiDocumentationService;
use Illuminate\Console\Command;

class GenerateApiDocumentation extends Command
{
    protected $signature = 'api:generate-docs 
                            {--format=all : Output format (json, markdown, or all)}
                            {--output= : Output directory path}';

    protected $description = 'Generate API documentation from routes and controllers';

    public function handle(ApiDocumentationService $docService): int
    {
        $this->info('Starting API documentation generation...');
        $this->newLine();

        // Generate documentation
        $documentation = $docService->generateDocumentation();
        
        $format = $this->option('format');
        $outputPath = $this->option('output') ?: base_path('docs/api');

        // Ensure output directory exists
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
            $this->info("Created output directory: {$outputPath}");
        }

        $filesGenerated = 0;

        // Generate JSON documentation
        if ($format === 'json' || $format === 'all') {
            $jsonContent = $docService->exportAsJson();
            $jsonPath = $outputPath . '/api-documentation.json';
            file_put_contents($jsonPath, $jsonContent);
            $this->info("✓ JSON documentation generated: {$jsonPath}");
            $filesGenerated++;
        }

        // Generate Markdown documentation
        if ($format === 'markdown' || $format === 'all') {
            $markdownContent = $docService->exportAsMarkdown();
            $markdownPath = $outputPath . '/API_DOCUMENTATION.md';
            file_put_contents($markdownPath, $markdownContent);
            $this->info("✓ Markdown documentation generated: {$markdownPath}");
            $filesGenerated++;
        }

        $this->newLine();
        $this->info("Documentation generation complete!");
        $this->info("Total files generated: {$filesGenerated}");
        
        // Display statistics
        $totalEndpoints = array_sum(array_map('count', $documentation));
        $moduleCount = count($documentation);
        
        $this->newLine();
        $this->info("Statistics:");
        $this->line("  Modules documented: {$moduleCount}");
        $this->line("  Total endpoints: {$totalEndpoints}");
        
        $this->newLine();
        $this->info("View documentation at:");
        $this->line("  Interactive UI: " . url('/api/documentation'));
        $this->line("  JSON format: " . url('/api/documentation/json'));
        $this->line("  Markdown format: " . url('/api/documentation/markdown'));

        return Command::SUCCESS;
    }
}
