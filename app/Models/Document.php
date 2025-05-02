<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'size',
        'type',
        'is_visible',
        'web_user_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'size' => 'integer',
    ];

    protected $appends = ['file_path_url'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(WebUser::class, 'web_user_id');
    }

    protected function filePathUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Verifica si la columna 'file_path' existe y tiene valor
                if (!empty($attributes['file_path']) && Storage::disk('public')->exists($attributes['file_path'])) {
                    // Retorna la URL completa generada por Laravel Storage
                    return Storage::disk('public')->url($attributes['file_path']);
                }
                return null;
            }
        );
    }
}
