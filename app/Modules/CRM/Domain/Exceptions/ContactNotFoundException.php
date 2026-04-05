<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Exceptions;
class ContactNotFoundException extends \RuntimeException {
    public function __construct(int $id) { parent::__construct("CRM Contact {$id} not found."); }
}
