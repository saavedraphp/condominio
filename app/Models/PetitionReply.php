<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PetitionReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'petition_id',
        'repliable_id',
        'repliable_type',
        'body',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    // Relación polimórfica: puede pertenecer a User o WebUser
    public function repliable(): MorphTo
    {
        return $this->morphTo();
    }
}
