<?php

namespace Modules\HR\Domain\Enums;

enum GoalStatus: string
{
    /** Goal has been created but not yet published to the employee. */
    case Draft     = 'draft';
    /** Goal is active and the employee is working towards it. */
    case Active    = 'active';
    /** Goal has been successfully met and marked as done. */
    case Completed = 'completed';
    /** Goal has been cancelled before completion. */
    case Cancelled = 'cancelled';
}
