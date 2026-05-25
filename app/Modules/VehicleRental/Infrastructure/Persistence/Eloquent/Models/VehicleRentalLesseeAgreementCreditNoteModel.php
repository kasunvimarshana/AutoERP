<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalLesseeAgreementCreditNoteModel extends CoreModel
{


    protected $table = 'vehicle_rental_lessee_agreement_credit_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}