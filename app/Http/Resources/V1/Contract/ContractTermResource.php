<?php

namespace App\Http\Resources\V1\Contract;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractTermResource extends JsonResource
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
            'contract' => $this->whenLoaded('contract', function () {
                return new ContractResource($this->contract);
            }),
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type,
        ];
    }
}

