<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = ['web_user_id', 'brand', 'model', 'plate_number'];

    public function web_user(): BelongsTo
    {
        return $this->belongsTo(WebUser::class);
    }
}
