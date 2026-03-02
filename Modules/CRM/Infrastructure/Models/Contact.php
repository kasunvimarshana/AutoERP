<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contact extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'contacts';
    protected $fillable = [
        'tenant_id','first_name','last_name','email','phone','mobile',
        'company_name','type','credit_limit','opening_balance','tax_number',
        'address','city','state','country','is_active',
    ];
    protected $casts = [
        'credit_limit'    => 'decimal:4',
        'opening_balance' => 'decimal:4',
        'is_active'       => 'boolean',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('contacts.tenant_id', app('tenant.id'));
        });
    }
    public function leads(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(Lead::class, 'contact_id');
    }
}
