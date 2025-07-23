<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousePaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'web_user_id' => $this->web_user_id,
            'house_id' => $this->house_id,
            'title' => $this->title,
            'description' => $this->description,
            'file_path' => $this->file_path,
            'file_path_url' => $this->file_path_url,
            'file_name' => $this->file_name,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date->format('Y-m-d'), // ¡Aquí está la magia!
            'transaction_code' => $this->transaction_code,
            'status' => $this->status,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

    }
}
