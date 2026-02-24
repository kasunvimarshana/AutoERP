<?php

namespace Modules\Communication\Domain\Enums;

enum ChannelType: string
{
    case Direct  = 'direct';
    case Group   = 'group';
    case Channel = 'channel';
}
