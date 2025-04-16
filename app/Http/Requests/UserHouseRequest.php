<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserHouseRequest extends FormRequest
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
            'web_user_id' => 'required|exists:web_users,id',
            'house_id' => 'required|exists:houses,id',
            'is_owner' => 'required|boolean',
            'is_resident' => 'required|boolean',
            'is_manager' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'house_id.required' => 'El id de la casa es obligatorio.',
            'is_owner.required' => 'El valor del campo es propietario es obligatorio',
            'is_resident.required' => 'El valor del campo es residente es obligatorio',
            'is_manager.required' => 'El valor del campo es administrador es obligatorio',
        ];
    }
}
