<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\HouseVehicle;

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
        $house_vehicle = $this->route('house_vehicle');
        $house_id = $this->input('house_id'); // Obtenemos el ID de la casa desde el input del request

        return [
            'house_id'     => ['required', 'exists:houses,id'],
            'brand'        => ['required', 'string', 'min:3', 'max:50'],
            'model'        => ['required', 'string', 'max:50'],
            'plate_number' => [
                'required',
                'string',
                'max:10',
                // Aquí está la magia:
                Rule::unique('house_vehicles', 'plate_number')
                    // 1. Ignorar el registro actual si estamos editando
                    ->ignore($house_vehicle?->id)
                    // 2. Añadir la condición de que la unicidad solo se aplica
                    //    para el `house_id` que se está enviando.
                    ->where('house_id', $house_id),
            ],
        ];

    }

    public function messages(): array
    {
        return [
            'plate_number.required' => 'El número de placa es obligatorio.',
            'plate_number.string' => 'La placa debe ser texto.',
            'plate_number.max' => 'La placa no puede exceder los :max caracteres.',
            'plate_number.unique' => 'Esta placa ya se encuentra registrada.',

            'brand.required' => 'La marca es obligatoria.',
            'model.required' => 'El modelo es obligatorio.',
            'year.required' => 'El año es obligatorio.',
            'year.integer' => 'El año debe ser un número.',
            'year.digits' => 'El año debe tener 4 dígitos.',
        ];
    }
}
