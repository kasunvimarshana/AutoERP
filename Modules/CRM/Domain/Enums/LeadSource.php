<?php
namespace Modules\CRM\Domain\Enums;
enum LeadSource: string
{
    case Website = 'website';
    case Social = 'social';
    case Referral = 'referral';
    case Advertisement = 'advertisement';
    case ColdCall = 'cold_call';
    case Email = 'email';
    case Event = 'event';
    case Other = 'other';
}
