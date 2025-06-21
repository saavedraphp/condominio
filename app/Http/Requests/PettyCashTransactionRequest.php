<?php

namespace App\Http\Requests;

use App\Models\PettyCashFund;
use Illuminate\Foundation\Http\FormRequest;

class PettyCashTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // La autorización principal será que el fondo al que se asocia esté abierto

        $fund = PettyCashFund::find($this->input('petty_cash_fund_id'));
        if ($fund) {
            return $fund->status === 'open'; // Solo se pueden hacer transacciones en fondos abiertos
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
            'petty_cash_fund_id' => 'required|integer|exists:petty_cash_funds,id',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:2048', // Max 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'petty_cash_fund_id.required' => 'El ID del fondo de caja menor es obligatorio.',
            'petty_cash_fund_id.exists' => 'El fondo de caja menor especificado no existe.',
            'transaction_date.required' => 'La fecha de la transacción es obligatoria.',
            'transaction_date.date' => 'La fecha de la transacción debe ser una fecha válida.',
            'description.required' => 'La descripción es obligatoria.',
            'type.required' => 'El tipo de transacción es obligatorio.',
            'type.in' => 'El tipo de transacción debe ser "income" o "expense".',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser al menos 0.01.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.mimes' => 'El archivo debe ser uno de los siguientes tipos: pdf, jpg, jpeg, png, doc, docx, xls, xlsx.',
            'file.max' => 'El archivo no puede exceder los 2MB.'
        ];
    }

}
