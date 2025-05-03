<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petition extends Model
{
    use HasFactory;

    protected $fillable = [
        'web_user_id',
        'type',
        'subject',
        'description',
        'status',
    ];


    public function webUser(): BelongsTo
    {
        return $this->belongsTo(WebUser::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(PetitionReply::class)->orderBy('created_at');
    }
}
