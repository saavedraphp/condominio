<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'visit_pass_id',
        'first_name',
        'last_name',
        'document_type_id',
        'document_number',
    ];

    /**
     * Obtiene el pase de visita al que pertenece este integrante.
     */
    public function visitPass(): BelongsTo
    {
        return $this->belongsTo(VisitPass::class);
    }
}
