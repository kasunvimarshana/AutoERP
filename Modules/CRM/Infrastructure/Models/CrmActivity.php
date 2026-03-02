<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CrmActivity extends Model {
    use HasFactory;
    protected $table = 'crm_activities';
    protected $fillable = [
        'tenant_id','lead_id','opportunity_id','contact_id',
        'type','subject','description','due_date','completed_at','assigned_to','outcome',
    ];
    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('crm_activities.tenant_id', app('tenant.id'));
        });
    }
    public function lead(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
