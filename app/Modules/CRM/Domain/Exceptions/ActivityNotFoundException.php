<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Exceptions;
class ActivityNotFoundException extends \RuntimeException {
    public function __construct(int $id) { parent::__construct("CRM Activity {$id} not found."); }
}
