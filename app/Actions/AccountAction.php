<?php

namespace App\Actions;

use App\Models\Client;

class AccountAction
{

    public function execute(Client $client): array
    {
        $holdings = $client->transactions()
            ->whereNotNull('instrument')
            ->selectRaw("
                instrument,
                SUM(
                    CASE
                        WHEN type = 'buy' THEN quantity
                        WHEN type = 'sell' THEN -quantity
                        ELSE 0
                    END
                ) as quantity
            ")
            ->groupBy('instrument')
            ->having('quantity', '>', 0)
            ->get()
            ->map(fn ($row) => [
                'instrument' => $row->instrument,
                'quantity' => (int) $row->quantity,
            ])
            ->values();

        return [
            'client' => $client,
            'holdings' => $holdings,
        ];
    }
}
