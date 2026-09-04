<?php

namespace App\Data;

use App\Models\Client;
use Illuminate\Support\Collection;

final readonly class AccountData
{
    public function __construct(
        public Client     $client,
        public Collection $holdings,
    )
    {
    }
}
