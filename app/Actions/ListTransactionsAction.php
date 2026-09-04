<?php

namespace App\Actions;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTransactionsAction
{
    public function execute(Client $client, array $filters): LengthAwarePaginator
    {
        return $client->transactions()
            ->when($filters['type'] ?? null, fn($query, $type) => $query->where('type', $type))
            ->when($filters['instrument'] ?? null, fn($query, $instrument) => $query->where('instrument', $instrument))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }
}
