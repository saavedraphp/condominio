<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'activatable_id',     // Asegúrate de que sean fillable si usas create()
        'activatable_type',
        'token',
    ];

    /**
     * Obtiene el modelo padre (User o WebUser) al que pertenece la activación.
     */
    public function activatable(): MorphTo
    {
        return $this->morphTo();
    }
}
