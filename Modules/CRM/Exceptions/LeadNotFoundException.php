<?php

declare(strict_types=1);

namespace Modules\CRM\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class LeadNotFoundException extends NotFoundException
{
    protected $code = 'LEAD_NOT_FOUND';

    protected $message = 'Lead not found';
}
