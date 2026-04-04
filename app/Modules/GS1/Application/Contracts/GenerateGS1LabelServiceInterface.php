<?php
namespace Modules\GS1\Application\Contracts;

use Modules\GS1\Application\DTOs\GS1LabelData;
use Modules\GS1\Domain\Entities\GS1Label;

interface GenerateGS1LabelServiceInterface
{
    public function execute(GS1LabelData $data): GS1Label;
}
