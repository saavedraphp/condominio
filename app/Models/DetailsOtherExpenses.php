<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailsOtherExpenses extends Model
{
    use HasFactory;
    protected $fillable = [
        'other_expense_id',
        'file_path',
        'original_filename',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'float',
    ];

    public function otherExpenses(): BelongsTo
    {
        return $this->belongsTo(OtherExpenses::class, 'other_expense_id', 'id');
    }
}
