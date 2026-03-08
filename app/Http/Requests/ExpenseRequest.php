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
        $rules = [
            'annual_budget_id' => 'required|exists:annual_budgets,id',
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:50',
            'amount' => 'required|numeric|gt:0',
            'expense_date' => 'required|date',
            'is_asset' => 'nullable|boolean',
            'asset_type' => 'required_if:is_asset,true,1|nullable|string',
            'asset_code' => 'nullable|string|max:10',
            'asset_brand' => 'nullable|string|max:50',
            'market_value' => 'nullable|numeric',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'annual_budget_id.required' => 'El campo presupuesto es obligatorio.',
            'annual_budget_id.exists' => 'El presupuesto seleccionado no es válido.',
            'title.required' => 'El campo título es obligatorio.',
            'description.required' => 'El campo descripción es obligatorio.',
            'amount.required' => 'El campo monto es obligatorio.',
            'amount.numeric' => 'El campo monto debe ser un número.',
            'amount.gt' => 'El campo monto debe ser mayor que cero.',
            'expense_date.required' => 'El campo fecha es obligatorio.',
            'expense_date.date' => 'El campo fecha debe ser una fecha válida.',
            'asset_type.required_if' => 'Si el gasto es un activo o suministro, debes seleccionar un tipo.',
        ];
    }
}
