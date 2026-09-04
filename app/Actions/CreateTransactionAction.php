<?php

namespace App\Actions;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientHoldingsException;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class CreateTransactionAction
{

    public function execute(Client $client, array $data): Transaction
    {
        return DB::transaction(function () use ($client, $data) {
            $lockedClient = Client::query()
                ->lockForUpdate()
                ->findOrFail($client->id);

            $type = TransactionType::from($data['type']);

            return match ($type) {
                TransactionType::Deposit => $this->deposit($lockedClient, $data['amount']),
                TransactionType::Withdrawal => $this->withdraw($lockedClient, $data['amount']),
                TransactionType::Buy => $this->buy($lockedClient, $data),
                TransactionType::Sell => $this->sell($lockedClient, $data),
            };
        });
    }

    private function deposit(Client $client, string $amount): Transaction
    {
        $client->increment('cash_balance', $amount);

        return $client->transactions()->create([
            'type' => TransactionType::Deposit,
            'amount' => $amount,
        ]);
    }

    private function withdraw(Client $client, string $amount): Transaction
    {
        if (bccomp($client->cash_balance, $amount, 2) < 0) {
            throw new InsufficientFundsException();
        }

        $client->decrement('cash_balance', $amount);

        return $client->transactions()->create([
            'type' => TransactionType::Withdrawal,
            'amount' => $amount,
        ]);
    }

    private function buy(Client $client, array $data): Transaction
    {
        $cost = bcmul((string)$data['quantity'], (string)$data['price_per_unit'], 2);

        if (bccomp($client->cash_balance, $cost, 2) < 0) {
            throw new InsufficientFundsException();
        }

        $client->decrement('cash_balance', $cost);

        return $client->transactions()->create([
                'type' => TransactionType::Buy,
                'amount' => $cost,
                'instrument' => $data['instrument'],
                'quantity' => $data['quantity'],
                'price_per_unit' => $data['price_per_unit'],
            ]
        );
    }

    private function sell(Client $client, array $data): Transaction
    {
        $owned = $this->currentHolding($client, $data['instrument']);

        if ($data['quantity'] > $owned) {
            throw new InsufficientHoldingsException($data['instrument'], $owned, $data['quantity']);
        }

        $proceeds = bcmul((string)$data['quantity'], (string)$data['price_per_unit'], 2);

        $client->increment('cash_balance', $proceeds);

        return $client->transactions()->create([
            'type' => TransactionType::Sell,
            'amount' => $proceeds,
            'instrument' => $data['instrument'],
            'quantity' => $data['quantity'],
            'price_per_unit' => $data['price_per_unit'],
        ]);
    }

    private function currentHolding(Client $client, string $instrument): int
    {
        return (int) ($client->transactions()
            ->holdings()
            ->where('instrument', $instrument)
            ->value('net_quantity') ?? 0);
    }
}
