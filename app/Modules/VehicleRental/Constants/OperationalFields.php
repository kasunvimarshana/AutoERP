<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class OperationalFields
{
    public const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:sP';

    public const DATABASE_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    public const UTC = 'UTC';

    public const HANDOVER_STATUS_REASON = 'Rental handover: ';

    public const RETURN_STATUS_REASON = 'Rental return: ';

    public const MUTABLE_USE = ['vehicle_id', 'owner_agreement_id', 'starts_at', 'ends_at', 'notes'];

    public const USE_RELATIONS = ['customerAgreement', 'ownerAgreement', 'vehicle', 'replacesUse'];
}
