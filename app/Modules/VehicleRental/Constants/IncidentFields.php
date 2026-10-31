<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class IncidentFields
{
    public const ATTACHMENT_MAX_KB = 10240;
    public const ATTACHMENT_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
    public const ATTACHMENT_ADDED = 'attachment_added';

    public const REFERENCE_PREFIX = 'RIN-';
    public const TYPE_LENGTH = 40;
    public const STATUS_LENGTH = 30;
    public const ACTION_LENGTH = 40;
    public const EVIDENCE_REFERENCE_LENGTH = 255;
    public const DESCRIPTION_LENGTH = 2000;
    public const REVIEW_REASON_LENGTH = 2000;
}
