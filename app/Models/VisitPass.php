<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitPass extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'creator_id',
        'creator_type',
        'house_id',
        'title',
        'details',
        'starts_at',
        'expires_at',
        'access_code',
        'is_locked',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    /**
     * Obtiene el modelo creador del pase (puede ser un User o un WebUser).
     */
    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Obtiene la casa a la que pertenece la visita.
     */
    public function house(): BelongsTo
    {
        // Asumiendo que tu modelo se llama House
        return $this->belongsTo(House::class);
    }

    /**
     * Obtiene todos los integrantes asociados a este pase.
     */
    public function members(): HasMany
    {
        return $this->hasMany(VisitMember::class);
    }

    /**
     * Obtiene todos los registros de acceso para este pase.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }
}
