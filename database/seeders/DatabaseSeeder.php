<?php

namespace Database\Seeders;

use App\Actions\CreateTransactionAction;
use App\Enums\TransactionType;
use App\Models\Client;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $action = app(CreateTransactionAction::class);

        // Client 1: Ana — demonstrates the example from the specification.
        $ana = Client::create(['name' => 'Ana', 'cash_balance' => 0]);

        $action->execute($ana, [
            'type' => TransactionType::Deposit->value,
            'amount' => 1000,
        ]);

        $action->execute($ana, [
            'type' => TransactionType::Buy->value,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $action->execute($ana, [
            'type' => TransactionType::Sell->value,
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price_per_unit' => 120,
        ]);

        // Client 2: Marko — demonstrates an independent account with a different portfolio.
        $marko = Client::create(['name' => 'Marko', 'cash_balance' => 0]);

        $action->execute($marko, [
            'type' => TransactionType::Deposit->value,
            'amount' => 5000,
        ]);

        $action->execute($marko, [
            'type' => TransactionType::Buy->value,
            'instrument' => 'TSLA',
            'quantity' => 10,
            'price_per_unit' => 250,
        ]);

        $action->execute($marko, [
            'type' => TransactionType::Buy->value,
            'instrument' => 'MSFT',
            'quantity' => 5,
            'price_per_unit' => 300,
        ]);

        $action->execute($marko, [
            'type' => TransactionType::Withdrawal->value,
            'amount' => 200,
        ]);

        // Client 3: Elena — demonstrates a newly created account with no transactions.
        Client::create(['name' => 'Elena', 'cash_balance' => 0]);
    }
}
