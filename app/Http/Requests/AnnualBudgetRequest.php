<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnualBudgetRequest extends FormRequest
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
        $id = $this->route('annual_budget');

        return [
            'budget_type_id' => 'required|exists:budget_types,id',
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('annual_budgets')
                    ->where(function ($query) {
                        return $query->where('budget_type_id', $this->input('budget_type_id'));
                    })
                    ->ignore($id),
            ],
            'amount' => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'year.unique' => 'Ya existe un presupuesto para ese tipo y año.',
            'budget_type_id.required' => 'El tipo de presupuesto es obligatorio.',
            'budget_type_id.exists' => 'El tipo de presupuesto seleccionado no es válido.',
            'year.required' => 'El año es obligatorio.',
            'year.integer' => 'El año debe ser un número.',
            'year.min' => 'El año debe ser mayor o igual a 2000.',
            'year.max' => 'El año debe ser menor o igual a 2100.',
        ];
    }
}
