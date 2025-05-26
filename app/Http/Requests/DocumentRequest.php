<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
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
            'title' => 'required|string|max:50',
            'description' => 'nullable|string:max:200',
        ];

        if ($this->isMethod('post')) {
            // --- CREACIÓN ---
            // El archivo es estrictamente requerido
            $rules['file_path'] = 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';
        }
        elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            // 'sometimes' significa: valida esto sólo si el campo está presente en la data de la solicitud.
            $rules['file_path'] = 'sometimes|required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'; // Ajusta mimes y max
        }

        return $rules;

    }

    public function messages(): array
    {
        return [
            'title.required' => 'El campo título es obligatorio.',
            'file_path.required' => 'El documento es obligatorio.',
            'file_path.mimes' => 'El formato del archivo es incorrecto.',
        ];
    }
}
