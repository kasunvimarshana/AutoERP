<?php

namespace App\Modules\CRMManagement\Events;

use App\Modules\CRMManagement\Models\Communication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunicationSent
{
    use Dispatchable, SerializesModels;

    public Communication $communication;

    public function __construct(Communication $communication)
    {
        $this->communication = $communication;
    }
}
