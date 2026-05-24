<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\DepositRequest;
use App\Http\Requests\Account\TransferRequest;
use App\Http\Responses\Account\TransactionResponse;
use App\Http\Responses\ApiResponse;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accountService) {}

    public function deposit(Request $request, Account $account, DepositRequest $depositRequest, ApiResponse $apiResponse): JsonResponse
    {
        $userId = $request->user()?->id;
        if ($userId === null) {
            throw new AuthenticationException;
        }

        $transaction = $this->accountService->deposit(
            accountId: $account->id,
            userId: (int) $userId,
            dto: $depositRequest->toDepositDto($request->header('X-Idempotency-Key')),
        );

        $apiResponse->data = new TransactionResponse(
            id: $transaction->id,
            amount: (string) $transaction->amount,
            type: $transaction->type,
            status: $transaction->status,
        );

        return new JsonResponse($apiResponse);
    }

    public function transfer(Request $request, Account $account, TransferRequest $transferRequest, ApiResponse $apiResponse): JsonResponse
    {
        $userId = $request->user()?->id;
        if ($userId === null) {
            throw new AuthenticationException;
        }

        $transaction = $this->accountService->transfer(
            fromAccountId: $account->id,
            userId: (int) $userId,
            dto: $transferRequest->toTransferDto($request->header('X-Idempotency-Key')),
        );

        $apiResponse->data = new TransactionResponse(
            id: $transaction->id,
            amount: (string) $transaction->amount,
            type: $transaction->type,
            status: $transaction->status,
        );

        // 202 Accepted — перевод принят, обрабатывается асинхронно
        return new JsonResponse($apiResponse, 202);
    }
}
