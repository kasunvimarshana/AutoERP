<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentModel extends Model
{
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
        'document_type_id',
        'document_number',
        'status',
        'owner_id',
        'party_id',
        'document_date',
        'due_date',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'data',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'data' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(DocumentItemModel::class, 'document_id')->orderBy('sequence');
    }

    public function attachments()
    {
        return $this->hasMany(DocumentAttachmentModel::class, 'document_id');
    }

    public function type()
    {
        return $this->belongsTo(DocumentTypeModel::class, 'document_type_id');
    }
}
