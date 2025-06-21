<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'annual_budget_id' => 'required|exists:annual_budgets,id',
            'description' => 'required|string|max:50',
            'amount' => 'required|numeric|gt:0',
            'expense_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'annual_budget_id.required' => 'El campo presupuesto es obligatorio.',
            'annual_budget_id.exists' => 'El presupuesto seleccionado no es válido.',
            'description.required' => 'El campo descripción es obligatorio.',
            'amount.required' => 'El campo monto es obligatorio.',
            'amount.numeric' => 'El campo monto debe ser un número.',
            'amount.gt' => 'El campo monto debe ser mayor que cero.',
            'expense_date.required' => 'El campo fecha es obligatorio.',
            'expense_date.date' => 'El campo fecha debe ser una fecha válida.',
        ];
    }
}
