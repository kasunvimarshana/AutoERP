<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoiceCopyType: string
{
    case Original = 'original';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
