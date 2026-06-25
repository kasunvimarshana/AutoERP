<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreementTerm extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_agreement_terms';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','sequence'=>'integer','is_printable'=>'boolean','is_active'=>'boolean','metadata'=>'array']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
}
