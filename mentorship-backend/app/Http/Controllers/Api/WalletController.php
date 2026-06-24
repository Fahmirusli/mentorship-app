<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\WithdrawalRequest;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $withdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'balance' => $user->wallet_balance ?? 0.00,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:50',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
        ]);

        $user = $request->user();

        // Check if user has enough balance
        $currentBalance = $user->wallet_balance ?? 0.00;
        
        if ($validated['amount'] > $currentBalance) {
            return response()->json([
                'message' => 'Insufficient wallet balance.',
            ], 400);
        }

        // Deduct balance and create request
        $user->wallet_balance -= $validated['amount'];
        $user->save();

        $withdrawal = WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully.',
            'withdrawal' => $withdrawal,
            'new_balance' => $user->wallet_balance,
        ], 201);
    }
}
