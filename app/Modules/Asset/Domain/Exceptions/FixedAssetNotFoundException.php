<?php
declare(strict_types=1);
namespace Modules\Asset\Domain\Exceptions;
class FixedAssetNotFoundException extends \RuntimeException {
    public function __construct(int $id) { parent::__construct("Fixed asset {$id} not found."); }
}
