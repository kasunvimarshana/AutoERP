<?php
namespace Modules\Auth\Domain\Enums;
enum TokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
}
