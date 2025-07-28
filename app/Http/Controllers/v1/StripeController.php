<?php

namespace App\Http\Controllers\v1;

use App\Models\StripePayment;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;


class StripeController extends Controller
{

    public function ChargeInvestmentWallet(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'payment_method'=>'required|string',
            'currency'=>'nullable|string'
        ]);
        try {

            $user=auth()->user();
            $walletType=$user->wallets()->where('wallet_type','investment');
            if(!$walletType)
            {
                return response()->json(['message'=>trans('messages.unauthorized')]);
            }

            $stripe = new StripeClient(env('STRIPE_SECRET'));

            $charge = $stripe->charges->create([
                'amount' => $request->amount,
                'currency' => $request->currency,
                'source' => $request->token,
                'description' => $request->description ?? 'عملية بدون وصف',
            ]);

            $amountInDollars = $request->amount / 100;

            DB::transaction(function () use ($request, $charge, $amountInDollars,$user) {
                // جلب محفظة الاستثمار
                $wallet = Wallet::where('user_id',$user->id )
                    ->where('wallet_type', 'investment')
                    ->firstOrFail();

                // تسجيل المعاملة
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amountInDollars,
                    'type' => 'deposit',
                    'status' => 'completed',
                    'stripe_payment_id' => $charge->id,
                ]);

                StripePayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_intent_id' => $charge->id,
                    'amount' => $amountInDollars,
                    'currency' => $charge->currency,
                    'payment_method' => $charge->payment_method ?? 'unknown',
                    'status' => $charge->status,
                    'receipt_url' => $charge->receipt_url ?? null,
                ]);

                $wallet->increment('balance', $amountInDollars);
            });

            return response()->json(['message' => __('messages.operation_success')]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $stripeCode = $e->getError()->code ?? 'generic_error';
            $translatedMessage = trans('stripe.' . $stripeCode);

            return response()->json([
                'message' => trans('messages.operation_failed') . $translatedMessage
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('messages.operation_failed') . $e->getMessage()
            ], 500);
        }
    }

}
