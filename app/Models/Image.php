<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'original_filename',
        'is_visible',
        'date_document',
        'order',
    ];

    /**
     * Obtiene el modelo padre al que pertenece la imagen (WebUser, Product, etc.).
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
