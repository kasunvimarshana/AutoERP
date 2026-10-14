<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Invoice\Models\Invoice;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Services\PurchaseDebitNoteService;

require __DIR__.'/../bootstrap/mysql.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $tenantId, $invoiceId, $noteId, $mode] = $argv;
try {
    app(TenantExecutionContextInterface::class)->runForTenant((int) $tenantId, function () use ($invoiceId, $noteId, $mode): void {
        DB::transaction(function () use ($invoiceId, $noteId, $mode): void {
            $invoice = Invoice::query()->findOrFail($invoiceId);
            $note = PurchaseDebitNote::query()->findOrFail($noteId);
            if ($mode === 'holder') {
                Invoice::query()->whereKey($invoiceId)->lockForUpdate()->firstOrFail();
                echo "locked\n";
                flush();
                if (trim((string) fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Test coordinator did not release the lock.');
                }
            } else {
                $connection = DB::selectOne('SELECT CONNECTION_ID() AS id');
                echo 'attempting:'.$connection->id."\n";
                flush();
            }
            app(PurchaseDebitNoteService::class)->allocate($note, $invoice, '8.000000', 1);
        });
    });
    echo "allocated\n";
} catch (ValidationException $exception) {
    echo json_encode(['rejected' => 'validation', 'errors' => $exception->errors()], JSON_THROW_ON_ERROR)."\n";
    exit(10);
} catch (InvalidArgumentException $exception) {
    echo json_encode(['rejected' => 'invariant', 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR)."\n";
    exit(11);
}
