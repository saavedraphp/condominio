<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('web_user')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules =  [
            'title' => 'required|string|max:50',
            'amount' => 'required|numeric|gt:0',
            'transaction_code' => 'required|string|max:20',
            'payment_date' => 'required|date',
        ];

        if ($this->isMethod('post')) {
            $rules['file_path'] = 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';
        }
        elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            // 'sometimes' significa: valida esto sólo si el campo está presente en la data de la solicitud.
            $rules['file_path'] = 'sometimes|required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'; // Ajusta mimes y max
        }
        return $rules;

    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del pago es obligatorio.',
            'title.string' => 'El título del pago debe ser una cadena de texto.',
            'title.max' => 'El título del pago no puede exceder los :max caracteres.',
            'amount.required' => 'El monto del pago es obligatorio.',
            'amount.numeric' => 'El monto del pago debe ser un número.',
            'amount.gt' => 'El monto del pago debe ser mayor que cero.',
            'transaction_code.required' => 'El código de transacción es obligatorio.',
            'transaction_code.string' => 'El código de transacción debe ser una cadena de texto.',
            'transaction_code.max' => 'El código de transacción no puede exceder los :max caracteres.',
            'payment_date.required' => 'La fecha de pago es obligatoria.',
            'payment_date.date' => 'La fecha de pago debe ser una fecha válida.',
            'file_path.required' => 'El archivo de comprobante de pago es obligatorio.',
            'file_path.image' => 'El archivo de comprobante de pago debe ser una imagen.',
            'file_path.mimes' => 'El archivo de comprobante de pago debe ser un archivo de tipo: :values.',
            'file_path.max' => 'El archivo de comprobante de pago no puede exceder los :max kilobytes.',
        ];

    }
}
