<?php

declare(strict_types=1);

namespace Modules\Document\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Document\Models\Document;

class DocumentDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Document $document, public bool $permanent = false) {}
}
