<?php
namespace Modules\Setting\Domain\Enums;
enum SettingType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Json = 'json';
    case Enum = 'enum';
}
