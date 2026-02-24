<?php
namespace Modules\Shared\Domain\Contracts;
interface UseCaseInterface
{
    public function execute(array $data): mixed;
}
