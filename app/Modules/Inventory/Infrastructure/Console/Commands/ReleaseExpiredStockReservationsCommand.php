<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Application\Contracts\ReleaseExpiredStockReservationsServiceInterface;
use Modules\Inventory\Domain\Events\ExpiredStockReservationsReleased;

class ReleaseExpiredStockReservationsCommand extends Command
{
    protected $signature = 'inventory:release-expired-reservations
        {--tenant_id= : Release only for a specific tenant id}
        {--expires_before= : Optional cutoff timestamp (Y-m-d H:i:s)}';

    protected $description = 'Release expired stock reservations and synchronize reserved quantity.';

    public function __construct(private readonly ReleaseExpiredStockReservationsServiceInterface $releaseExpiredService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantOption = $this->option('tenant_id');
        $expiresBefore = $this->option('expires_before');

        $tenantIds = $tenantOption !== null && $tenantOption !== ''
            ? [(int) $tenantOption]
            : $this->resolveActiveTenantIds();

        if ($tenantIds === []) {
            $this->info('No active tenants found.');

            return self::SUCCESS;
        }

        $releasedTotal = 0;

        foreach ($tenantIds as $tenantId) {
            $released = $this->releaseExpiredService->execute($tenantId, is_string($expiresBefore) ? $expiresBefore : null);
            $releasedTotal += $released;

            Event::dispatch(new ExpiredStockReservationsReleased(
                tenantId: $tenantId,
                releasedCount: $released,
                expiresBefore: is_string($expiresBefore) ? $expiresBefore : null,
            ));

            Log::info('Expired stock reservations released.', [
                'tenant_id' => $tenantId,
                'released_count' => $released,
                'expires_before' => is_string($expiresBefore) ? $expiresBefore : null,
            ]);

            $this->line(sprintf('Tenant %d: released %d expired reservations.', $tenantId, $released));
        }

        $this->info(sprintf('Completed. Released %d expired reservations in total.', $releasedTotal));

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function resolveActiveTenantIds(): array
    {
        return DB::table('tenants')
            ->where('active', true)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
