<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'category' => $this->category,
            'image' => $this->image,
            'is_open' => $this->is_open,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}