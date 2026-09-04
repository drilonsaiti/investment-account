<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_reflects_full_scenario_from_spec(): void
    {
        $client = Client::factory()->create(['cash_balance' => 0]);

        $this->postJson(route('transactions.store', $client), [
            'type' => 'deposit', 'amount' => 1000,
        ]);

        $this->postJson(route('transactions.store', $client), [
            'type' => 'buy', 'instrument' => 'AAPL', 'quantity' => 5, 'price_per_unit' => 100,
        ]);

        $this->postJson(route('transactions.store', $client), [
            'type' => 'sell', 'instrument' => 'AAPL', 'quantity' => 3, 'price_per_unit' => 120,
        ]);

        $response = $this->getJson(route('clients.account', $client));

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'cash_balance' => '860.00',
                'holdings' => [
                    ['instrument' => 'AAPL', 'quantity' => 2],
                ],
            ],
        ]);
    }
}
