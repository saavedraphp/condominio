<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'visit_pass_id',
        'code_entered',
        'event_type',
        'status',
        'remarks',
    ];

    /**
     * Obtiene el usuario (vigilante) que registró el acceso.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el pase de visita asociado al registro (si existe).
     */
    public function visitPass(): BelongsTo
    {
        return $this->belongsTo(VisitPass::class);
    }
}
