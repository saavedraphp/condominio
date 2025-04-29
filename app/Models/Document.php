<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'uploaded_by_web_user_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(WebUser::class, 'uploaded_by_web_user_id');
    }
}
