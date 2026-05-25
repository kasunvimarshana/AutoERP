<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Configuration\Application\Contracts\UseCases\ListConfigurationsServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationQueryData;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Core\Application\DTO\PagedResult;

final class ConfigListCommand extends Command
{
    protected $signature = 'config:list
        {--prefix= : Filter keys by prefix}
        {--source= : Filter by persisted source column value}
        {--page=1 : Result page number}
        {--per-page=' . ConfigurationDefaults::DEFAULT_PER_PAGE . ' : Items per page}';

    protected $description = 'List configuration entries';

    public function __construct(private readonly ListConfigurationsServiceInterface $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->execute(new ConfigurationQueryData(
            $this->option('prefix') !== null ? (string) $this->option('prefix') : null,
            $this->option('source') !== null ? (string) $this->option('source') : null,
            (int) $this->option('page'),
            (int) $this->option('per-page'),
        ));

        if ($result->isFailure()) {
            $this->error($result->error()?->message ?? 'Unable to list configuration.');

            return self::FAILURE;
        }

        $page = $result->value();
        if (! $page instanceof PagedResult) {
            $this->error('Invalid list response.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($page->items as $item) {
            if ($item instanceof ConfigurationValueData) {
                $rows[] = [
                    'key' => $item->key,
                    'value' => $this->formatValue($item->value),
                    'source' => $item->source,
                    'description' => $item->description ?? '',
                    'updated_at' => $item->updatedAt ?? '',
                ];
            }
        }

        $this->table(['key', 'value', 'source', 'description', 'updated_at'], $rows);
        $this->line(sprintf(
            'Page %d/%d | Total: %d',
            $page->page,
            $page->pageCount(),
            $page->total,
        ));

        return self::SUCCESS;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value);

        return is_string($encoded) ? $encoded : '[unserializable]';
    }
}
