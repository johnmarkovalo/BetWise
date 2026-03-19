<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAccountRequest;
use App\Http\Requests\Api\V1\UpdateAccountRequest;
use App\Http\Requests\Api\V1\UpdateBalanceRequest;
use App\Http\Resources\AccountCollection;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(public AccountService $accountService) {}

    public function index(Request $request): AccountCollection
    {
        $query = Account::query()->with(['team', 'capital']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        return new AccountCollection($query->paginate(20));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->accountService->createAccount($request->validated());

        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    public function show(Account $account): AccountResource
    {
        $account->load(['team', 'capital']);

        return new AccountResource($account);
    }

    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $account->update($request->validated());

        return new AccountResource($account->fresh());
    }

    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return response()->json(null, 204);
    }

    public function updateBalance(UpdateBalanceRequest $request, Account $account): AccountResource
    {
        $validated = $request->validated();

        $this->accountService->updateBalance(
            account: $account,
            amount: (float) $validated['amount'],
            operation: $validated['operation'],
            reason: $validated['reason'],
        );

        $account->load(['team', 'capital']);

        return new AccountResource($account);
    }
}
