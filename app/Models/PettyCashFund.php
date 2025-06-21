<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // For accessors/mutators

class PettyCashFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'white_label_id',
        'opening_date',
        'opening_balance',
        'responsible_person',
        'description',
        'status',
        'closing_date',
        'counted_closing_balance',
        'calculated_balance_at_closing', // Importante
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
        'opening_balance' => 'decimal:2',
        'counted_closing_balance' => 'decimal:2',
        'calculated_balance_at_closing' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(PettyCashTransaction::class);
    }

    // Accessor para el saldo actual calculado
    public function currentBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status === 'closed' && $this->calculated_balance_at_closing !== null) {
                    // Si está cerrado y tenemos el balance calculado al cierre, usamos ese.
                    return $this->calculated_balance_at_closing;
                }

                $income = $this->transactions()->where('type', 'income')->sum('amount');
                $expense = $this->transactions()->where('type', 'expense')->sum('amount');
                return ($this->opening_balance + $income) - $expense;
            }
        );
    }

    // Accessor para la diferencia al cierre
    public function difference(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status === 'closed' && $this->counted_closing_balance !== null && $this->calculated_balance_at_closing !== null) {
                    return $this->counted_closing_balance - $this->calculated_balance_at_closing;
                }
                return null;
            }
        );
    }

    // Accessor para saber si tiene transacciones (útil para UI)
    public function hasTransactions(): Attribute
    {
        return Attribute::make(
            //get: fn () => $this->transactions()->exists()
            get: fn () => isset($this->transactions_count) && is_numeric($this->transactions_count) && $this->transactions_count > 0

        );
    }

    // Al cerrar el fondo, se debe calcular y guardar el `calculated_balance_at_closing`
    public function closeFund(string $closingDate, float $countedBalance)
    {
        if ($this->status === 'open') {
            $this->calculated_balance_at_closing = $this->current_balance; // Calcula el balance actual ANTES de cerrar
            $this->closing_date = $closingDate;
            $this->counted_closing_balance = $countedBalance;
            $this->status = 'closed';
            $this->save();
        }
    }
}
