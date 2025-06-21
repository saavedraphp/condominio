<?php

// app/Models/PettyCashTransaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_id',
        'petty_cash_fund_id',
        'transaction_date',
        'description',
        'type',
        'amount',
        'file_path',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function pettyCashFund()
    {
        return $this->belongsTo(PettyCashFund::class);
    }

    // Si tienes una tabla WhiteLabel
    // public function whiteLabel()
    // {
    //     return $this->belongsTo(WhiteLabel::class);
    // }
}
