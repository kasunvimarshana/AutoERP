<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\Auditable;
use App\Models\User;

class Country extends Model
{
    use HasFactory, SoftDeletes, HasUuid, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'iso2',
        'iso3',
        'phone_code',
        'currency_code',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the country.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the country.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if country is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
