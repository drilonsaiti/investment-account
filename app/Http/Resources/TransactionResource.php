<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type->value,
            'amount' => (string)$this->amount,
            'instrument' => $this->instrument,
            'quantity' => $this->quantity,
            'price_per_unit' => $this->price_per_unit !== null ? (string)$this->price_per_unit : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
