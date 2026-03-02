<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Opportunity extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'opportunities';
    protected $fillable = [
        'tenant_id','lead_id','contact_id','title','stage',
        'value','probability','expected_close_date','assigned_to','notes',
    ];
    protected $casts = [
        'value'              => 'decimal:4',
        'probability'        => 'decimal:4',
        'expected_close_date'=> 'date',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('opportunities.tenant_id', app('tenant.id'));
        });
    }
    public function lead(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
