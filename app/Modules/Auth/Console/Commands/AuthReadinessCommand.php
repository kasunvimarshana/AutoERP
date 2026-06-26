<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Services\Readiness\AuthReadinessService;

final class AuthReadinessCommand extends Command
{
    protected $signature = 'auth:readiness';

    protected $description = 'Verify Auth configuration, schema, cache and critical container bindings.';

    public function handle(AuthReadinessService $readiness): int
    {
        $result = $readiness->inspect();
        $this->table(
            ['Check', 'Status', 'Message'],
            array_map(static fn (array $check): array => [
                $check['name'],
                $check['ready'] ? 'READY' : 'FAILED',
                $check['message'],
            ], $result['checks']),
        );

        if (! $result['ready']) {
            $this->error('Auth readiness failed. Do not route login traffic to this deployment.');

            return self::FAILURE;
        }

        $this->info('Auth readiness passed.');

        return self::SUCCESS;
    }
}
