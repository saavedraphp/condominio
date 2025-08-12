<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitPassRequest extends FormRequest
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
        $id = $this->route('visitPass') ? $this->route('visitPass')->id : null;

        return [
            'title' => 'required|string|min:2|max:50',
            'details' => 'nullable|string|max:100',
            'house_id' => 'required|exists:houses,id',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:starts_at',
/*            'access_code' => [
                'nullable',
                // Le decimos a la regla que ignore el ID del registro actual
                // al buscar duplicados en la tabla'.
                Rule::unique('visit_passes', 'access_code')->ignore($id),
            ]*/
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.string' => 'El título debe ser una cadena de texto.',
            'title.min' => 'El título debe tener al menos :min caracteres.',
            'title.max' => 'El título no puede exceder los :max caracteres.',
            'details.string' => 'Los detalles deben ser una cadena de texto.',
            'details.max' => 'Los detalles no pueden exceder los :max caracteres.',
            'house_id.required' => 'La casa es obligatoria.',
            'house_id.exists' => 'La casa seleccionada no es válida.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'starts_at.date' => 'La fecha de inicio debe ser una fecha válida.',
            'expires_at.required' => 'La fecha de expiración es obligatoria.',
            'expires_at.date' => 'La fecha de expiración debe ser una fecha válida.',
            'expires_at.after_or_equal' => 'La fecha de expiración debe ser igual o posterior a la fecha de inicio.',
/*            'access_code.required' => 'El código de acceso es obligatorio.',
            'access_code.unique' => 'El código de acceso ya existe.'*/

        ];
    }
}
