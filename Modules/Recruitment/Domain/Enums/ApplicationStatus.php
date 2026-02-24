<?php

namespace Modules\Recruitment\Domain\Enums;

enum ApplicationStatus: string
{
    case New       = 'new';
    case InReview  = 'in_review';
    case Interview = 'interview';
    case Offer     = 'offer';
    case Hired     = 'hired';
    case Rejected  = 'rejected';
}
