<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Vehicle;

class VehicleRequest extends FormRequest
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
        $vehicle = $this->route('vehicle');
        $rules =  [
            // --- Agrega aquí las reglas para tus otros campos ---
            'brand' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            /*'year' => 'required|integer|digits:4|min:1900|max:' . (date('Y') + 1), // Ejemplo para año
            'color' => 'nullable|string|max:30',*/
            // Si los vehículos pertenecen a un usuario:
            // 'user_id' => 'required|exists:users,id', // Asegura que el user_id exista en la tabla users
        ];

        // --- Regla Condicional para 'plate_number' ---
        $plateRules = [
            'required',
            'string',
            'max:10', // Ajusta si es necesario
        ];

        // Construir la regla unique
        $uniqueRule = Rule::unique('vehicles', 'plate_number');

        // Si $vehicle existe (estamos en modo UPDATE), ignorar su propio ID
        if ($vehicle) {
            $uniqueRule->ignore($vehicle->id);
            // Opcional: Si algún campo solo es requerido al crear y no al editar,
            // puedes modificar $rules aquí. Ejemplo:
            // $rules['campo_solo_requerido_al_crear'] = 'sometimes|required|...';
        }

        // Añadir la regla unique (configurada para store o update) a las reglas de plate_number
        $plateRules[] = $uniqueRule;

        // Añadir el conjunto completo de reglas para plate_number al array principal
        $rules['plate_number'] = $plateRules;

        return $rules;
    }

    public function messages(): array
    {
        return [
            'plate_number.required' => 'El número de placa es obligatorio.',
            'plate_number.string'   => 'La placa debe ser texto.',
            'plate_number.max'      => 'La placa no puede exceder los :max caracteres.',
            'plate_number.unique'   => 'Esta placa ya se encuentra registrada.',

            'brand.required' => 'La marca es obligatoria.',
            'model.required' => 'El modelo es obligatorio.',
            'year.required' => 'El año es obligatorio.',
            'year.integer' => 'El año debe ser un número.',
            'year.digits' => 'El año debe tener 4 dígitos.',
        ];
    }
}
