<?php

namespace App\Modules\JobCardManagement\Events;

use App\Modules\JobCardManagement\Models\JobCard;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobCardInvoiced
{
    use Dispatchable, SerializesModels;

    public JobCard $jobCard;

    public function __construct(JobCard $jobCard)
    {
        $this->jobCard = $jobCard;
    }
}
