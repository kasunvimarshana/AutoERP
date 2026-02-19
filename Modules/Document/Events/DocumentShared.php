<?php

declare(strict_types=1);

namespace Modules\Document\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Document\Models\DocumentShare;

class DocumentShared
{
    use Dispatchable, SerializesModels;

    public function __construct(public DocumentShare $share) {}
}
