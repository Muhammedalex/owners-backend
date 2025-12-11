<?php

namespace App\Http\Resources\V1\Setting;

use App\Http\Resources\V1\Ownership\OwnershipResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
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
            'ownership_id' => $this->ownership_id,
            'ownership' => $this->whenLoaded('ownership', function () {
                return new OwnershipResource($this->ownership);
            }),
            'key' => $this->key,
            'value' => $this->getTypedValue(),
            'value_type' => $this->value_type,
            'group' => $this->group,
            'description' => $this->description,
            'is_system_wide' => $this->isSystemWide(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

