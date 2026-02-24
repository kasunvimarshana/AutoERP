<?php

namespace Modules\Contracts\Domain\Enums;

enum ContractType: string
{
    case Service     = 'service';
    case Subscription = 'subscription';
    case Maintenance = 'maintenance';
    case Supply      = 'supply';
    case Other       = 'other';
}
