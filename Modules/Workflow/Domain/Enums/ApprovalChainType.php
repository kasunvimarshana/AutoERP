<?php

namespace Modules\Workflow\Domain\Enums;

enum ApprovalChainType: string
{
    case Sequential = 'sequential';
    case Parallel   = 'parallel';
    case AnyOf      = 'any_of';
}
