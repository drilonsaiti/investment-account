<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateTransactionAction;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientHoldingsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Client $client): JsonResponse
    {
        return TransactionResource::collection(
            $client->transactions()->latest()->paginate(20)
        )->response();
    }

    public function store(StoreTransactionRequest $request, Client $client, CreateTransactionAction $action): JsonResponse
    {
        try {
            $transaction = $action->execute($client, $request->validated());
        } catch (InsufficientFundsException|InsufficientHoldingsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return TransactionResource::make($transaction)
            ->response()
            ->setStatusCode(201);
    }
}
