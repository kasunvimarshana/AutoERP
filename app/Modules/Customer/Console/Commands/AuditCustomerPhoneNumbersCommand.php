<?php

declare(strict_types=1);

namespace Modules\Customer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Customer\Services\CustomerPhoneService;
use RuntimeException;

class AuditCustomerPhoneNumbersCommand extends Command
{
    protected $signature = 'customers:audit-phone-numbers {tenant : Business code} {--output= : Write a CSV review report}';

    protected $description = 'Read-only audit of missing, duplicate, and invalid customer phone/WhatsApp numbers';

    public function handle(CustomerPhoneService $phones, TenantExecutionContextInterface $context): int
    {
        $tenant = DB::table('tenants')->where('code', $this->argument('tenant'))->first();
        if ($tenant === null) {
            $this->error('Business code was not found.');

            return self::FAILURE;
        }
        $groups = [];
        $rows = [];
        $entries = $context->runForTenant((int) $tenant->id, fn (): array => iterator_to_array($phones->entries((int) $tenant->id)));
        foreach ($entries as $entry) {
            if ($entry['number'] === null) {
                $rows[] = $this->row(($entry['missing'] ?? false) ? 'Missing number' : 'Invalid number', $entry);

                continue;
            }
            $groups[$entry['number']][] = $entry;
        }
        foreach ($groups as $entries) {
            if (count(array_unique(array_column($entries, 'customer_id'))) < 2) {
                continue;
            }
            foreach ($entries as $entry) {
                $rows[] = $this->row('Shared by customers', $entry);
            }
        }

        $headers = ['Issue', 'Normalized number', 'Customer code', 'Customer name', 'Field', 'Original number', 'Archived'];
        if ($this->option('output')) {
            $stream = fopen((string) $this->option('output'), 'wb');
            if ($stream === false) {
                throw new RuntimeException('Could not open the audit CSV for writing.');
            }
            try {
                foreach ([$headers, ...$rows] as $row) {
                    $safeRow = array_map(static fn (string $value): string => preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, $row);
                    if (fputcsv($stream, $safeRow, ',', '"', '') === false) {
                        throw new RuntimeException('Could not write the audit CSV.');
                    }
                }
            } finally {
                fclose($stream);
            }
            $this->info('Review report written to '.$this->option('output'));
        } else {
            $this->table($headers, $rows);
        }
        $this->info(count($rows).' entries need review. No customer records were changed.');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $entry @return array<int, string> */
    private function row(string $issue, array $entry): array
    {
        return [$issue, (string) $entry['number'], $entry['code'], $entry['name'], $entry['field'], $entry['original'], $entry['archived'] ? 'Yes' : 'No'];
    }
}
