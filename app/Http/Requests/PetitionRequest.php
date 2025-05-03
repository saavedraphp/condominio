<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PetitionRequest extends FormRequest
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
            'type' => ['required','string','max:100',
                Rule::in(['Reclamo', 'Sugerencia', 'Consulta', 'Otro']) // Debe ser uno de estos valores exactos
            ],
            'subject' => 'required|string|max:50',
            'description' => 'required|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Debes seleccionar un tipo.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede tener más de :max caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',
        ];
    }
}
