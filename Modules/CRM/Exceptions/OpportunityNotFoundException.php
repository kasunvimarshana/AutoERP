<?php

declare(strict_types=1);

namespace Modules\CRM\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class OpportunityNotFoundException extends NotFoundException
{
    protected $code = 'OPPORTUNITY_NOT_FOUND';

    protected $message = 'Opportunity not found';
}
