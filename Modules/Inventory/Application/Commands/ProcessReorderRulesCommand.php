<?php

namespace Modules\Inventory\Application\Commands;

use Illuminate\Console\Command;
use Modules\Inventory\Infrastructure\Jobs\CheckReorderRuleJob;
use Modules\Inventory\Infrastructure\Models\ReorderRuleModel;

class ProcessReorderRulesCommand extends Command
{
    protected $signature = 'inventory:process-reorder-rules
                            {--tenant= : Limit processing to a specific tenant ID}
                            {--chunk=100 : Number of rules to process per chunk}';

    protected $description = 'Evaluate active reorder rules in chunks and dispatch async jobs to prevent execution timeout';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $chunkSize = (int) $this->option('chunk');

        $query = ReorderRuleModel::where('is_active', true);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No active reorder rules found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} active reorder rule(s) in chunks of {$chunkSize}...");
        $dispatched = 0;

        // orderBy is required for chunk() to produce stable, non-overlapping pages.
        $query->orderBy('id')->chunk($chunkSize, function ($rules) use (&$dispatched) {
            foreach ($rules as $rule) {
                CheckReorderRuleJob::dispatch($rule->id);
                $dispatched++;
            }
        });

        $this->info("Dispatched {$dispatched} job(s) to the queue.");

        return self::SUCCESS;
    }
}
