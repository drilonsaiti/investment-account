<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ],
            'cash_balance' => (string)$this->client->cash_balance,
            'holdings' => $this->holdings->map(fn($holding) => [
                'instrument' => $holding->instrument,
                'quantity' => (int) $holding->net_quantity,
            ]),
        ];
    }
}
