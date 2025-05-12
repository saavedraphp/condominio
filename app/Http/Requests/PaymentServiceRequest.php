<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentServiceRequest extends FormRequest
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
        $rules = [
            'service_id' => 'required|numeric',
            'house_id' => 'required|exists:houses,id',
            'payment_date' => 'required|date',
            'observations' => 'nullable|string:max:200',
        ];

        if ($this->isMethod('post')) {
            // --- CREACIÓN ---
            // El archivo es estrictamente requerido
            $rules['file_path'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
        }
        elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            // 'sometimes' significa: valida esto sólo si el campo está presente en la data de la solicitud.
            $rules['file_path'] = 'sometimes|required|file|mimes:jpg,jpeg,png|max:2048'; // Ajusta mimes y max
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Tiene que definir el tipo de servicio.',
            'file_path.required' => 'La imagen es obligatorio.',
            'file_path.mimes' => 'El formato del archivo es incorrecto.',
        ];
    }
}
