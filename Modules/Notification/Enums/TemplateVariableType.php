<?php

declare(strict_types=1);

namespace Modules\Notification\Enums;

enum TemplateVariableType: string
{
    case STRING = 'string';
    case NUMBER = 'number';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case BOOLEAN = 'boolean';
    case ARRAY = 'array';
    case OBJECT = 'object';
}
