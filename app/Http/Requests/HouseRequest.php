<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HouseRequest extends FormRequest
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
            'payment_code' => 'required|string|min:4|max:10',
            'property_unit' => 'required',
            'address' => 'required|string|min:5|max:50',
            'construction_area' => 'required',
            'participation_percentage' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_code.required' => 'El código de pago es obligatorio.',
            'payment_code.min' => 'El código de pago tiene que tener como minimo :min caracteres.',
            'payment_code.max' => 'El código de pago no puede exceder los :max caracteres.',
            'property_unit.required' => 'La unidad de la propiedad es obligatorio.',
            'address.required' => 'La dirección es obligatorio.',
            'address.min' => 'La dirección tiene que tener como minimo :min caracteres.',
            'address.max' => 'La dirección no puede exceder los :max caracteres.',
            'construction_area.required' => 'El area de construccion es obligatorio',
            'participation_percentage.required' => 'El porcentaje de parcipación es obligatorio',
        ];
    }
}
