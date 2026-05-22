<?php

declare(strict_types=1);

namespace Modules\Item\Domain\Enums;

enum ItemAttributeType: string
{
    case Text = 'TEXT';
    case Select = 'SELECT';
    case Number = 'NUMBER';
    case Boolean = 'BOOLEAN';
}
