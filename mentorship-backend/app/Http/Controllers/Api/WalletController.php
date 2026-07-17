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
        
        $withdrawalsList = WithdrawalRequest::where('user_id', $user->id)
            ->select('id', 'created_at as date', 'amount', 'status')
            ->get()
            ->map(function($item) {
                $arr = $item->toArray();
                $arr['type'] = 'withdrawal';
                return $arr;
            });

        $hourlyRate = $user->mentorProfile->hourly_rate ?? 50;

        $earningsList = $user->mentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->join('users as mentee', 'mentorships.mentee_id', '=', 'mentee.id')
            ->where('appointments.status', 'completed')
            ->select('appointments.id', 'appointments.updated_at as date', 'appointments.fee as amount', 'mentee.name as mentee_name')
            ->get()
            ->map(function($item) use ($hourlyRate) {
                $arr = $item->toArray();
                $arr['type'] = 'payment';
                if ($arr['amount'] == null || $arr['amount'] == 0) {
                    $arr['amount'] = $hourlyRate;
                }
                return $arr;
            });

        $transactions = $earningsList->concat($withdrawalsList)->sortByDesc('date')->values();

        // Dynamically calculate actual earnings from completed appointments
        $earnings = $earningsList->sum('amount');

        $totalWithdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'paid'])
            ->sum('amount');

        $actualBalance = $earnings - $totalWithdrawals;

        // Auto-fix DB mismatch caused by dummy data
        if ($user->wallet_balance != $actualBalance) {
            $user->wallet_balance = $actualBalance;
            $user->save();
        }

        return response()->json([
            'balance' => $user->wallet_balance ?? 0.00,
            'transactions' => $transactions,
            'withdrawals' => $withdrawalsList,
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
