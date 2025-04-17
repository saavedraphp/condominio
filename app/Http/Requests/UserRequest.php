<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'required|string|min:2|max:50',
            'email' => 'email',
            'phone' => 'required|numeric|min:7',
            'status' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre tiene que tener como minimo :min caracteres.',
            'name.max' => 'El nombre no puede exceder los :max caracteres.',
            'email.email' => 'El correo es obligatorio.',
            'phone.required' => 'El teléfono es obligatorio',
            'phone.numeric' => 'El teléfono es tiene que ser númerico',
            'phone.min' => 'El teléfono tiene que tener minimo :min números',
        ];
    }
}
