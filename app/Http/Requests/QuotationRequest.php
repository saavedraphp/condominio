<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuotationRequest extends FormRequest
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
            'company_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // Max 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'El nombre de la empresa es obligatorio.',
            'company_name.string' => 'El nombre de la empresa debe ser una cadena de texto.',
            'company_name.max' => 'El nombre de la empresa no puede exceder los 255 caracteres.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número válido.',
            'amount.min' => 'El monto debe ser al menos 0.01.',
            'file.file' => 'Debe proporcionar un archivo válido.',
            'file.mimes' => 'El archivo debe ser un PDF, DOC o DOCX.',
            'file.max' => 'El archivo no puede exceder los 5 MB.',
        ];

    }
}
