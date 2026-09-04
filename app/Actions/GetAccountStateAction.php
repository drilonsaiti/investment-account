<?php

namespace App\Actions;

use App\Data\AccountData;
use App\Models\Client;

class GetAccountStateAction
{

    public function execute(Client $client): AccountData
    {
        $holdings = $client->transactions()
            ->holdings()
            ->having('net_quantity', '>', 0)
            ->get();

        return new AccountData(
            client: $client,
            holdings: $holdings,
        );
    }
}
