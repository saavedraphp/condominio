<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdRequest extends FormRequest
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
            'title' => 'required|string|min:10|max:50',
            'description' => 'required|string|min:20|max:250',
            'start_day' => 'required|date',
            'end_day' => 'required|date|after_or_equal:start_day',
            'active' => 'required',
        ];

        if ($this->isMethod('post')) {
            // --- CREACIÓN ---
            // El archivo es estrictamente requerido
            $rules['file_path'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
        }

        elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            // 'sometimes' significa: valida esto sólo si el campo está presente en la data de la solicitud.
            $rules['file_path'] = 'sometimes|required|file|mimes:jpg,jpeg,png|max:2048'; // Ajusta mimes y max
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.min' => 'El título debe tener al menos :min caracteres.',
            'title.max' => 'El título no puede exceder los :max caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.min' => 'La descripción debe tener al menos :min caracteres.',
            'end_day.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'file_path.required' => 'La imagen es obligatorio.',
            'file_path.mimes' => 'El formato del archivo es incorrecto.',
        ];
    }
}
