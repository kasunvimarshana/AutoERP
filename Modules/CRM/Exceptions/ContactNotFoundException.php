<?php

declare(strict_types=1);

namespace Modules\CRM\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

class ContactNotFoundException extends NotFoundException
{
    protected $code = 'CONTACT_NOT_FOUND';

    protected $message = 'Contact not found';
}
