<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Reporting\DTOs\ReportDefinition;

final class ReportFilename
{
    public function make(ReportDefinition $definition, string $extension): string
    {
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $definition->key) ?: 'report';

        return trim($base, '-').'.'.$extension;
    }
}
