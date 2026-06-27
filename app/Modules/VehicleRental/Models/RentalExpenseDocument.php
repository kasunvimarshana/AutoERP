<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\PrivateObject\Models\PrivateObject;

final class RentalExpenseDocument extends TenantOwnedModel
{
    protected $table = 'rental_expense_documents';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tenant_id'=>'integer','organization_unit_id'=>'integer','expense_id'=>'integer','private_object_id'=>'integer','created_by'=>'integer'];
    }

    public function expense(): BelongsTo { return $this->belongsTo(RentalExpense::class, 'expense_id'); }
    public function privateObject(): BelongsTo { return $this->belongsTo(PrivateObject::class, 'private_object_id'); }
}
