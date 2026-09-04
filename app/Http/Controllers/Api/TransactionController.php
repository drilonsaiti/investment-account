<?php

namespace App\Http\Controllers\Api;

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
            $client->transactions()->paginate(20)
        )->response();
    }

    public function store(StoreTransactionRequest $request, Client $client): JsonResponse
    {
        abort(501, 'Not implemented yet');
    }
}
