<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'restaurant_id' => $this->restaurant_id,
            'driver_id' => $this->driver_id,
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}