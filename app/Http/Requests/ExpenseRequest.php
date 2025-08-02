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
        ];

        if ($this->isMethod('post')) {
            // --- CREACIÓN ---
            // El archivo es estrictamente requerido
            $rules['file_path'] = 'required|file|mimes:jpg,jpeg,png|max:2048';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            // 'sometimes' significa: valida esto sólo si el campo está presente en la data de la solicitud.
            $rules['file_path'] = 'sometimes|required|file|mimes:jpg,jpeg,png|max:2048'; // Ajusta mimes y max
        }

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
            'file_path.file' => 'El campo archivo debe ser un archivo.',
            'file_path.mimes' => 'El archivo debe ser de tipo: jpg, jpeg, png, pdf.',
            'file_path.max' => 'El archivo no debe exceder los 2MB.',
        ];
    }
}
