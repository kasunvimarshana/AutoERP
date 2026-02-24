<?php

namespace Modules\AssetManagement\Domain\Enums;

enum AssetStatus: string
{
    case Active           = 'active';
    case UnderMaintenance = 'under_maintenance';
    case Disposed         = 'disposed';
}
