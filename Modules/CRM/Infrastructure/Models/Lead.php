<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\Domain\Enums\LeadStatus;
class Lead extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'leads';
    protected $fillable = [
        'tenant_id','contact_id','title','status','source',
        'value','expected_close_date','assigned_to','notes',
    ];
    protected $casts = [
        'value'              => 'decimal:4',
        'expected_close_date'=> 'date',
        'status'             => LeadStatus::class,
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('leads.tenant_id', app('tenant.id'));
        });
    }
    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(CrmActivity::class, 'lead_id');
    }
    public function opportunities(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(Opportunity::class, 'lead_id');
    }
}
