<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtherExpenses extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'date',
        'white_label_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public  function detailsOtherExpenses(): HasMany
    {
        return $this->hasMany(DetailsOtherExpenses::class, 'other_expense_id', 'id');

    }
}
