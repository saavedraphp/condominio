<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseResident extends Model
{
    use HasFactory;

    protected $fillable = ['house_id', 'name', 'email', 'phone', 'birth_date', 'relation_type'];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }
}
