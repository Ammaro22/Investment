<?php

namespace App\Http\Controllers;

use App\Models\InternalTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Mail\SendEmailOtp;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuthController;
class WalletController extends Controller
{

    public function transferToPlatform(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        $amount = $request->amount;

        try {
            DB::transaction(function () use ($user, $amount) {
                $investmentWallet = $user->wallets()
                    ->where('wallet_type', 'investment')
                    ->lockForUpdate()
                    ->firstOrFail();

                $platformWallet = Wallet::where('wallet_type', 'platform')
                    ->whereHas('user', function ($query) {
                        $query->where('role_id', 1);
                    })
                    ->lockForUpdate()
                    ->firstOrFail();

                $admin = User::select('id')->where('role_id', 1)->firstOrFail();

                if ($investmentWallet->balance < $amount) {
                    throw new \Exception('رصيد غير كافي في محفظة الاستثمار');
                }

                $transferOut = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $investmentWallet->id,
                    'amount' => -$amount,
                    'type' => 'transfer_out',
                    'status' => 'completed',
                ]);

                $transferIn = Transaction::create([
                    'user_id' => $admin->id,
                    'wallet_id' => $platformWallet->id,
                    'amount' => $amount,
                    'type' => 'transfer_in',
                    'status' => 'completed',
                    'related_transaction_id' => $transferOut->id,
                ]);

                InternalTransfer::create([
                    'sender_wallet_id' => $investmentWallet->id,
                    'receiver_wallet_id' => $platformWallet->id,
                    'amount' => $amount,
                    'notes' => 'تحويل إلى محفظة المنصة',
                ]);

                $investmentWallet->decrement('balance', $amount);
                $platformWallet->increment('balance', $amount);
            });

            return response()->json(['message' => trans('messages.operation_success')]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

}
