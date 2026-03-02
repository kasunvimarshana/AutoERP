<?php

declare(strict_types=1);

namespace Modules\Customization\Domain\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Url = 'url';
    case Textarea = 'textarea';
}
