<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{

    use RefreshDatabase;

    public function test_deposit_increases_cash_balance()
    {
        $client = Client::factory()->create();

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Deposit,
            'amount' => 1000,
        ]);

        $response->assertCreated();
        $this->assertEquals('1000.00', $client->fresh()->cash_balance);
    }

    public function test_valid_withdrawal_decreases_cash_balance(): void
    {
        $client = Client::factory()->create(['cash_balance' => 500]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Withdrawal,
            'amount' => 200,
        ]);

        $response->assertCreated();
        $this->assertEquals('300.00', $client->fresh()->cash_balance);
    }

    public function test_withdrawal_larger_than_balance_is_rejected(): void
    {
        $client = Client::factory()->create(['cash_balance' => 100]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Withdrawal,
            'amount' => 500,
        ]);

        $response->assertStatus(422);
        $this->assertEquals('100.00', $client->fresh()->cash_balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_valid_buy_decreases_cash_and_increases_holdings(): void
    {
        $client = Client::factory()->create(['cash_balance' => 1000]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Buy,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response->assertCreated();
        $this->assertEquals('500.00', $client->fresh()->cash_balance);

        $account = $this->getJson(route('clients.account', $client))->json()['data'];
        $this->assertEquals(5, $account['holdings'][0]['quantity']);
    }

    public function test_buy_larger_than_available_cash_is_rejected(): void
    {
        $client = Client::factory()->create(['cash_balance' => 100]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Buy,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response->assertStatus(422);
        $this->assertEquals('100.00', $client->fresh()->cash_balance);
        $this->assertDatabaseCount('transactions', 0);

    }

    public function test_valid_sell_increases_cash_and_reduces_holdings(): void
    {
        $client = Client::factory()->create(['cash_balance' => 1000]);

        $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Buy,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Sell,
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price_per_unit' => 120,
        ]);

        $response->assertCreated();
        $this->assertEquals('860.00', $client->fresh()->cash_balance);

        $account = $this->getJson(route('clients.account', $client))->json()['data'];
        $this->assertEquals(2, $account['holdings'][0]['quantity']);
    }

    public function test_sell_greater_than_holdings_is_rejected(): void
    {
        $client = Client::factory()->create(['cash_balance' => 1000]);

        $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Buy,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Sell,
            'instrument' => 'AAPL',
            'quantity' => 8,
            'price_per_unit' => 100,
        ]);

        $response->assertStatus(422);
        $this->assertEquals('500.00', $client->fresh()->cash_balance);
    }

    public function test_client_transactions_are_isolated(): void
    {
        $clientA = Client::factory()->create(['cash_balance' => 0]);
        $clientB = Client::factory()->create(['cash_balance' => 0]);

        $this->postJson(route('transactions.store', $clientA), [
            'type' => TransactionType::Deposit,
            'amount' => 1000,
        ]);

        $this->assertEquals('1000.00', $clientA->fresh()->cash_balance);
        $this->assertEquals('0.00', $clientB->fresh()->cash_balance);
    }

    public function test_negative_amount_is_rejected(): void
    {
        $client = Client::factory()->create();

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Deposit,
            'amount' => -100,
        ]);

        $response->assertStatus(422);
    }

    public function test_buy_without_instrument_is_rejected(): void
    {
        $client = Client::factory()->create(['cash_balance' => 1000]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Buy,
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response->assertStatus(422);
    }

    public function test_deposit_with_instrument_is_rejected(): void
    {
        $client = Client::factory()->create(['cash_balance' => 1000]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Deposit,
            'amount' => 100,
            'instrument' => 'AAPL',
        ]);

        $response->assertStatus(422);
    }

    public function test_transactions_endpoint_has_no_update_or_delete_route(): void
    {
        $client = Client::factory()->create();
        $transaction = $client->transactions()->create([
            'type' => 'deposit', 'amount' => 100,
        ]);

        $this->putJson("/api/clients/{$client->id}/transactions/{$transaction->id}")->assertStatus(404);
        $this->deleteJson("/api/clients/{$client->id}/transactions/{$transaction->id}")->assertStatus(404);

    }

    public function test_transaction_for_nonexistent_client_returns_404(): void
    {
        $nonExistentClientId = 9999;

        $this->postJson(
            route('transactions.store', [$nonExistentClientId]),
            [
                'type' => 'deposit',
                'amount' => 100,
            ]
        )->assertStatus(404);
    }

    public function test_withdrawal_equal_to_full_balance_succeeds(): void
    {
        $client = Client::factory()->create(['cash_balance' => 300]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Withdrawal,
            'amount' => 300,
        ]);

        $response->assertCreated();
        $this->assertEquals('0.00', $client->fresh()->cash_balance);
    }

    public function test_sell_equal_to_full_holding_succeeds(): void
    {
        $client = Client::factory()->create(['cash_balance' => 1000]);

        $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Buy,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response = $this->postJson(route('transactions.store', $client), [
            'type' => TransactionType::Sell,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response->assertCreated();

        $account = $this->getJson(route('clients.account', $client))->json()['data'];
        $this->assertEquals([], $account['holdings']);
    }
}
