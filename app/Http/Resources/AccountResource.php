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
                'id' => $this->resource['client']->id,
                'name' => $this->resource['client']->name,
            ],
            'cash_balance' => (string) $this->resource['client']->cash_balance,
            'holdings' => $this->resource['holdings'],
        ];
    }
}
