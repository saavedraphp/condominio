<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProyectRequest extends FormRequest
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
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'additional_expenses' => 'nullable|numeric|min:0',
            'details' => 'nullable|string|max:500',
            'quotations' => 'nullable|array',
            'quotations.*.company_name' => 'required_with:quotations|string|max:50',
            'quotations.*.amount' => 'required_with:quotations|numeric|min:0.01',
            'quotations.*.file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proyecto es obligatorio.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de finalización es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
            'additional_expenses.numeric' => 'Los gastos adicionales deben ser un número válido.',
            'details.max' => 'Los detalles no pueden exceder los 500 caracteres.',
            'quotations.*.company_name.required_with' => 'El nombre de la empresa es obligatorio si se proporciona una cotización.',
            'quotations.*.amount.required_with' => 'El monto es obligatorio si se proporciona una cotización.',
            'quotations.*.file.mimes' => 'El archivo debe ser un PDF, DOC o DOCX.',
            'quotations.*.file.max' => 'El archivo no puede exceder los 5 MB.',
        ];
    }
}
