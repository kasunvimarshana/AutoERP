<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Validate critical configuration values
 * 
 * Ensures all required production configurations are properly set
 */
class ValidateConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:validate 
                            {--production : Validate production-specific requirements}
                            {--warnings : Show warnings for optional but recommended configs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate critical configuration values for security and functionality';

    /**
     * Configuration validation rules
     */
    private array $criticalConfigs = [
        'APP_KEY' => [
            'check' => 'not_empty',
            'message' => 'APP_KEY must be set. Run: php artisan key:generate',
            'severity' => 'critical',
        ],
        'JWT_SECRET' => [
            'check' => 'not_empty_in_production',
            'message' => 'JWT_SECRET must be explicitly set in production. Generate: php -r "echo base64_encode(random_bytes(32));"',
            'severity' => 'critical',
        ],
        'DB_CONNECTION' => [
            'check' => 'not_empty',
            'message' => 'DB_CONNECTION must be configured',
            'severity' => 'critical',
        ],
        'JWT_ALGORITHM' => [
            'check' => 'in_list',
            'allowed' => ['HS256', 'HS384', 'HS512'],
            'message' => 'JWT_ALGORITHM must be one of: HS256, HS384, HS512',
            'severity' => 'error',
        ],
    ];

    private array $warningConfigs = [
        'APP_DEBUG' => [
            'check' => 'false_in_production',
            'message' => 'APP_DEBUG should be false in production to prevent information leakage',
            'severity' => 'warning',
        ],
        'JWT_REQUIRE_HTTPS' => [
            'check' => 'true_in_production',
            'message' => 'JWT_REQUIRE_HTTPS should be true in production for security',
            'severity' => 'warning',
        ],
        'BCRYPT_ROUNDS' => [
            'check' => 'min_value',
            'min' => 12,
            'message' => 'BCRYPT_ROUNDS should be at least 12 for security',
            'severity' => 'warning',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Validating configuration...');
        $this->newLine();

        $errors = 0;
        $warnings = 0;
        $isProduction = app()->environment('production') || $this->option('production');

        // Validate critical configs
        foreach ($this->criticalConfigs as $key => $rule) {
            $result = $this->validateConfig($key, $rule, $isProduction);
            
            if ($result['status'] === 'error') {
                $errors++;
                $this->error("❌ {$result['message']}");
            } elseif ($result['status'] === 'warning') {
                $warnings++;
                if ($this->option('warnings')) {
                    $this->warn("⚠️  {$result['message']}");
                }
            } elseif ($result['status'] === 'ok') {
                $this->line("✅ {$key}: OK");
            }
        }

        // Validate warning configs if requested
        if ($this->option('warnings')) {
            $this->newLine();
            $this->info('📋 Checking recommended configurations...');
            
            foreach ($this->warningConfigs as $key => $rule) {
                $result = $this->validateConfig($key, $rule, $isProduction);
                
                if ($result['status'] === 'warning') {
                    $warnings++;
                    $this->warn("⚠️  {$result['message']}");
                } elseif ($result['status'] === 'ok') {
                    $this->line("✅ {$key}: OK");
                }
            }
        }

        // Summary
        $this->newLine();
        if ($errors > 0) {
            $this->error("❌ Configuration validation failed: {$errors} error(s)");
            return self::FAILURE;
        }

        if ($warnings > 0 && $this->option('warnings')) {
            $this->warn("⚠️  Configuration has {$warnings} warning(s)");
        }

        $this->info('✅ Configuration validation passed!');
        
        return self::SUCCESS;
    }

    /**
     * Validate a specific configuration
     */
    private function validateConfig(string $key, array $rule, bool $isProduction): array
    {
        $value = env($key);
        $check = $rule['check'];

        switch ($check) {
            case 'not_empty':
                if (empty($value)) {
                    return [
                        'status' => 'error',
                        'message' => $rule['message'],
                    ];
                }
                break;

            case 'not_empty_in_production':
                if ($isProduction && empty($value)) {
                    return [
                        'status' => 'error',
                        'message' => $rule['message'],
                    ];
                } elseif (!$isProduction && empty($value)) {
                    return [
                        'status' => 'warning',
                        'message' => "{$key} is not set (using fallback)",
                    ];
                }
                break;

            case 'false_in_production':
                if ($isProduction && $value !== 'false' && $value !== false) {
                    return [
                        'status' => 'warning',
                        'message' => $rule['message'],
                    ];
                }
                break;

            case 'true_in_production':
                if ($isProduction && $value !== 'true' && $value !== true) {
                    return [
                        'status' => 'warning',
                        'message' => $rule['message'],
                    ];
                }
                break;

            case 'in_list':
                if (!in_array($value, $rule['allowed'], true)) {
                    return [
                        'status' => 'error',
                        'message' => $rule['message'],
                    ];
                }
                break;

            case 'min_value':
                if ((int)$value < $rule['min']) {
                    return [
                        'status' => 'warning',
                        'message' => $rule['message'],
                    ];
                }
                break;
        }

        return ['status' => 'ok', 'message' => "{$key} is valid"];
    }
}
