<?php

// app/Http/Resources/HouseResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Datos que SIEMPRE se devolverán
        return  $data = [
            'id' => $this->id,
            'address' => $this->address,
            'property_unit' => $this->property_unit,
            'ownership_structure_details' => $this->ownership_structure_details,
            'name_first_member' => $this->firstResident?->name,
            'owner' => $this->owner_name, // ejemplo
            $this->mergeWhen($request->boolean('with_balance'), [
                'balance' => $this->calculateBalance()
            ]),
        ];
    }
}
