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
            'payment_code' => 'nullable|string',
            'property_unit' => 'nullable',
            'address' => 'required|string|min:5|max:50',
            'construction_area' => 'nullable|numeric',
            'participation_percentage' => 'nullable',
            'ownership_structure' => 'required',
            'opening_balance' => 'required|numeric',
            'is_department' => 'nullable|boolean',
            'is_lot' => 'nullable|boolean',
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
            'opening_balance.required' => 'El saldo de apertura es obligatorio',
            'opening_balance.numeric' => 'El saldo de apertura debe ser un número',
            'participation_percentage.required' => 'El porcentaje de parcipación es obligatorio',
        ];
    }
}
