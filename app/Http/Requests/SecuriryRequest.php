<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SecuriryRequest extends FormRequest
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
        $securityId = $this->route('security') ? $this->route('security')->id : null;

        $rules =  [
            'name' => 'required|string|min:2|max:50',
            'email' => [
                'required',
                'email',
                // Le decimos a la regla que ignore el ID del registro actual
                // al buscar duplicados en la tabla 'users'.
                Rule::unique('users', 'email')->ignore($securityId),
            ],
            'phone' => 'required|numeric|min:9',
            'status' => 'required',
        ];
        if ($this->isMethod('post')) {
            $rules['file_path'] = 'nullable|file|mimes:jpg,jpeg,png|max:10240';
        }
        elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['file_path'] = 'sometimes|nullable|file|mimes:jpg,jpeg,png|max:10240'; // Ajusta mimes y max
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre tiene que tener como minimo :min caracteres.',
            'name.max' => 'El nombre no puede exceder los :max caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está en uso por otro usuario.',
            'phone.required' => 'El teléfono es obligatorio',
            'phone.numeric' => 'El teléfono es tiene que ser númerico',
            'phone.min' => 'El teléfono tiene que tener minimo :min números',
            'file_path.file' => 'El archivo debe ser un archivo válido.',
            'file_path.mimes' => 'El formato del archivo es incorrecto. Debe ser jpg, jpeg o png.',
        ];
    }
}
