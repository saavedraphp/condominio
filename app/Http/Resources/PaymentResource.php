<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'house_id' => $this->house_id,
            'address' => $this->whenLoaded('house', function () {
                return $this->house->address;
            }, 'Sin dirección'),
            'amount' => round((float)$this->amount, 2),
            'payment_date' => $this->payment_date ? Carbon::parse($this->payment_date)->format('Y-m-d') : 'No disponible',
            'transaction_code' => $this->transaction_code ?? 'No disponible',
        ];
    }
}
