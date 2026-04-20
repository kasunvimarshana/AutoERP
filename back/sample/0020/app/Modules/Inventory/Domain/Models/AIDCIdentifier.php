<?php

namespace App\Modules\Core\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AIDCIdentifier extends Model {
    protected $table = 'aidc_identifiers';
    protected $fillable = ['tenant_id', 'identifier_value', 'linkable_type', 'linkable_id'];

    public function linkable(): MorphTo {
        return $this->morphTo();
    }
}
