<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRelationModel extends Model
{
    protected $table = 'document_relations';

    protected $fillable = [
        'source_document_id',
        'target_document_id',
        'relation_type',
    ];
}
