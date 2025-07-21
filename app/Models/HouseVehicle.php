<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseVehicle extends Model
{
    use HasFactory;
    protected $fillable = ['house_id', 'brand', 'model', 'plate_number'];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }
}
