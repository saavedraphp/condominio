<?php

namespace App\Http\Requests;

use App\Models\PettyCashFund;
use Illuminate\Foundation\Http\FormRequest;

class ClosePettyCashFundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $fund = $this->route('petty_cash_fund');
        if ($fund instanceof PettyCashFund) {
            return $fund->status === 'open'; // Solo se puede cerrar un fondo abierto
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'closing_date' => 'required|date|after_or_equal:petty_cash_fund.opening_date',
            'counted_closing_balance' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'closing_date.required' => 'La fecha de cierre es obligatoria.',
            'closing_date.date' => 'La fecha de cierre debe ser una fecha válida.',
            'closing_date.after_or_equal' => 'La fecha de cierre debe ser igual o posterior a la fecha de apertura.',
            'counted_closing_balance.required' => 'El saldo contado al cierre es obligatorio.',
            'counted_closing_balance.numeric' => 'El saldo contado al cierre debe ser un número.',
            'counted_closing_balance.min' => 'El saldo contado al cierre no puede ser negativo.',
        ];

    }
}
