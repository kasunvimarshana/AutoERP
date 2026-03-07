<?php

namespace App\Console\Commands;

use App\Models\SagaLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Inspects and recovers stuck or failed saga runs.
 *
 * Usage:
 *   php artisan saga:recover --list              # show all failed/stuck sagas
 *   php artisan saga:recover --stuck=30          # show sagas stuck > 30 min
 *   php artisan saga:recover --saga-id=<uuid>    # show detail for a specific saga
 */
class RecoverSagaCommand extends Command
{
    protected $signature = 'saga:recover
                            {--list         : List all failed or compensated sagas}
                            {--stuck=30     : List sagas stuck in "started" state for more than N minutes}
                            {--saga-id=     : Show detailed info for a specific saga ID}';

    protected $description = 'Inspect and recover stuck or failed distributed sagas';

    public function handle(): int
    {
        if ($sagaId = $this->option('saga-id')) {
            return $this->showSaga($sagaId);
        }

        if ($this->option('list')) {
            return $this->listFailed();
        }

        $minutes = (int) ($this->option('stuck') ?? 30);
        return $this->listStuck($minutes);
    }

    private function showSaga(string $sagaId): int
    {
        $log = SagaLog::where('saga_id', $sagaId)->first();

        if (!$log) {
            $this->error("No saga found with ID: {$sagaId}");
            return self::FAILURE;
        }

        $this->info("Saga: {$log->saga_id}");
        $this->table(
            ['Field', 'Value'],
            [
                ['Type',          $log->saga_type],
                ['Status',        $log->status],
                ['Current Step',  $log->current_step ?? '-'],
                ['Error',         $log->error_message ?? '-'],
                ['Started',       $log->created_at],
                ['Completed',     $log->completed_at ?? '-'],
            ]
        );

        if (!empty($log->compensation_log)) {
            $this->newLine();
            $this->line('<comment>Compensation log:</comment>');
            $this->table(
                ['Step', 'Status', 'Error'],
                array_map(fn ($entry) => [
                    $entry['step']   ?? '-',
                    $entry['status'] ?? '-',
                    $entry['error']  ?? '-',
                ], $log->compensation_log)
            );
        }

        return self::SUCCESS;
    }

    private function listFailed(): int
    {
        $logs = SagaLog::failed()
            ->orWhere('status', SagaLog::STATUS_COMPENSATED)
            ->orderByDesc('created_at')
            ->get(['saga_id', 'saga_type', 'status', 'current_step', 'error_message', 'created_at']);

        if ($logs->isEmpty()) {
            $this->info('No failed or compensated sagas found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Saga ID', 'Type', 'Status', 'Failed Step', 'Error', 'Created At'],
            $logs->map(fn ($l) => [
                $l->saga_id,
                $l->saga_type,
                $l->status,
                $l->current_step ?? '-',
                substr($l->error_message ?? '-', 0, 60),
                $l->created_at,
            ])->all()
        );

        Log::info('[RecoverSagaCommand] Listed ' . $logs->count() . ' failed/compensated sagas.');

        return self::SUCCESS;
    }

    private function listStuck(int $minutes): int
    {
        $logs = SagaLog::stuck($minutes)
            ->orderBy('created_at')
            ->get(['saga_id', 'saga_type', 'status', 'current_step', 'created_at']);

        if ($logs->isEmpty()) {
            $this->info("No sagas stuck for more than {$minutes} minutes.");
            return self::SUCCESS;
        }

        $this->warn("Found {$logs->count()} saga(s) stuck in 'started' state for > {$minutes} minutes:");

        $this->table(
            ['Saga ID', 'Type', 'Current Step', 'Started At'],
            $logs->map(fn ($l) => [
                $l->saga_id,
                $l->saga_type,
                $l->current_step ?? '-',
                $l->created_at,
            ])->all()
        );

        Log::warning('[RecoverSagaCommand] Found ' . $logs->count() . " stuck saga(s) (threshold: {$minutes}min).");

        return self::SUCCESS;
    }
}
