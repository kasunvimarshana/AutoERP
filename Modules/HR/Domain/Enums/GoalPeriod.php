<?php

namespace Modules\HR\Domain\Enums;

enum GoalPeriod: string
{
    case Q1      = 'q1';
    case Q2      = 'q2';
    case Q3      = 'q3';
    case Q4      = 'q4';
    case Annual  = 'annual';
    case Monthly = 'monthly';
    case Custom  = 'custom';
}
