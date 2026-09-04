<?php

namespace App\Http\Controllers\Api;

use App\Actions\AccountAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{

    public function index(): JsonResponse
    {
        return ClientResource::collection(Client::paginate(20))->response();
    }

    public function show(Client $client): JsonResponse
    {
        return ClientResource::make($client)->response();
    }

    public function account(Client $client, AccountAction $action): JsonResponse
    {
        return AccountResource::make(
            $action->execute($client)
        )->response();
    }
}
