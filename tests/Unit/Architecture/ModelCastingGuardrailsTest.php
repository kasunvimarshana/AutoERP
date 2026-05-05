<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class ModelCastingGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_critical_transaction_models_keep_required_state_and_version_casts(): void
    {
        $requiredCasts = [
            'app/Modules/Customer/Infrastructure/Persistence/Eloquent/Models/CustomerModel.php' => [
                "'type' =>",
                "'status' =>",
            ],
            'app/Modules/Finance/Infrastructure/Persistence/Eloquent/Models/JournalEntryModel.php' => [
                "'status' =>",
                "'entry_type' =>",
                "'row_version' =>",
            ],
            'app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Models/PurchaseOrderModel.php' => [
                "'status' =>",
                "'row_version' =>",
            ],
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Models/SalesOrderModel.php' => [
                "'status' =>",
                "'row_version' =>",
            ],
            'app/Modules/Warehouse/Infrastructure/Persistence/Eloquent/Models/WarehouseLocationModel.php' => [
                "'type' =>",
                "'capacity' =>",
            ],
        ];

        foreach ($requiredCasts as $relativePath => $needles) {
            $contents = $this->readSource($relativePath);

            foreach ($needles as $needle) {
                $this->assertStringContainsString(
                    $needle,
                    $contents,
                    'Missing required model cast contract in: '.$relativePath.' -> '.$needle
                );
            }
        }
    }

    private function readSource(string $relativePath): string
    {
        $fullPath = $this->repoRoot.'/'.$relativePath;
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $contents;
    }
}
