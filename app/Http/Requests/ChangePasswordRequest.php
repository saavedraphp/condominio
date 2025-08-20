<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Solo los usuarios autenticados pueden cambiar su contraseña.
        // La ruta ya está protegida por 'auth:sanctum', así que esto es una doble capa de seguridad.
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
            // La regla 'current_password' comprueba la contraseña contra la del usuario autenticado.
            'current_password' => ['required', 'current_password'],
            'new_password' => [
                'required',
                'confirmed', // Requiere que haya un campo 'new_password_confirmation' con el mismo valor.
                Password::min(8) // Mínimo 8 caracteres.
                ->letters() // Debe contener letras.
//                ->mixedCase() // Debe contener mayúsculas y minúsculas.
    //            ->numbers() // Debe contener números.
  //              ->symbols() // Debe contener símbolos.
            ],
        ];
    }

    /**
     * Get the custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',

            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'new_password.min' => 'La nueva contraseña debe tener al menos :min caracteres.',
            // Para la regla Password::class, Laravel usa mensajes más detallados que puedes traducir
            // en tus archivos de idioma (lang/es/validation.php). Por ejemplo:
            // 'The :attribute must contain at least one uppercase and one lowercase letter.'
            // que se traduciría como 'El campo :attribute debe contener al menos una mayúscula y una minúscula.'
            // El mensaje de 'min' de arriba es un ejemplo si quisieras sobrescribir solo esa parte.
        ];
    }
}
