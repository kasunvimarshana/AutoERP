<?php

namespace Modules\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent
{
    use Dispatchable, SerializesModels;

    public function __construct()
    {
        //
    }
}
