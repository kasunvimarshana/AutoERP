<?php

namespace Modules\Core\Domain\Entities;

abstract class BaseEntity
{
    public function __construct(public readonly ?int $id = null) {}
}
