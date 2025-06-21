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
        $vehicle = optional($this->route('vehicle'));
        $rules = [
            'web_user_id' => 'required|exists:web_users,id',
            'brand' => 'required|string|min:3|max:50',
            'model' => 'required|string|max:50',
        ];

        $plateRules = [
            'required',
            'string',
            'max:10',
        ];

        $uniqueRule = Rule::unique('vehicles', 'plate_number');

        if ($this->isMethod('put')) {
            $vehicle = $this->route('vehicle');
            if ($vehicle) {
                $uniqueRule->ignore($vehicle->id);
            }
        }

        $plateRules[] = $uniqueRule;

        $rules['plate_number'] = $plateRules;

        return $rules;
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
