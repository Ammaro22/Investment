<?php

namespace App\Http\Controllers;

use App\Models\InternalTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Mail\SendEmailOtp;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuthController;
class WalletController extends Controller
{


    public function ShowInvestmentWallet()
    {
        $user=auth()->user();

        $userRole=$user->role_id;

        if(!$user||$userRole!=2)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }


         $InvestmentWallet=Wallet::where('user_id',$user->id)->where('wallet_type','investment')->get();

        $Wallets=$InvestmentWallet->map(function ($wallet)
        {
            return $this->Format_timeStamp_Map($wallet);
        });


        return  response()->json([
            'message'=>trans('messages.operation_success'),
            'data'=>$Wallets
         ]);
    }


    public function ShowProfitWallet()
    {
        $user=auth()->user();

        $userRole=$user->role_id;

        if( !$user||$userRole!=2)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }


        $profitWallet=Wallet::where('user_id',$user->id)->where('wallet_type','profits')->get();

        $Wallets=$profitWallet->map(function ($wallet)
        {
            return $this->Format_timeStamp_Map($wallet);
        });

        return  response()->json([
            'message'=>trans('messages.operation_success'),
            'data'=>$Wallets
        ]);
    }


    public function ShowPlatformWallet()
    {
        $user=auth()->user();

        $userRole=$user->role_id;

        if( !$user||$userRole!=1)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }


        $platformWallet=Wallet::where('user_id',$user->id)->where('wallet_type','platform')->get();

        $Wallets=$platformWallet->map(function ($wallet)
        {
           return $this->Format_timeStamp_Map($wallet);
        });
        return  response()->json([
            'message'=>trans('messages.operation_success'),
            'data'=>$Wallets
        ]);
    }









    /*call within invest function  */

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

    /*call within TransferProfitJob*/
    public function transferFromPlatform(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $amount = $request->amount;


        try {
            DB::transaction(function () use ($user, $amount) {
                $profit_wallet = $user->wallets()
                    ->where('wallet_type', 'profits')
                    ->lockForUpdate()
                    ->firstOrFail();

                $platformWallet = Wallet::where('wallet_type', 'platform')
                    ->whereHas('user', function ($query) {
                        $query->where('role_id', 1);
                    })
                    ->lockForUpdate()
                    ->firstOrFail();


                $admin = User::select('id')->where('role_id', 1)->firstOrFail();

                $transferOut = Transaction::create([
                    'user_id' => $admin->id,
                    'wallet_id' => $platformWallet->id,
                    'amount' => -$amount,
                    'type' => 'transfer_out',
                    'status' => 'completed',
                ]);

                $transferIn = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $profit_wallet->id,
                    'amount' => $amount,
                    'type' => 'transfer_in',
                    'status' => 'completed',
                    'related_transaction_id' => $transferOut->id,
                ]);

                InternalTransfer::create([
                    'sender_wallet_id' => $platformWallet->id,
                    'receiver_wallet_id' => $profit_wallet->id,
                    'amount' => $amount,
                    'notes' => 'تحويل إلى محفظة الأرباح',
                ]);

                $platformWallet->decrement('balance', $amount);
                $profit_wallet->increment('balance', $amount);
            });

            return response()->json(['message' => trans('messages.operation_success')]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }








    public function Format_timeStamp_Map($wallets)
    {

        $ArrayWallet=$wallets->toArray();
        $ArrayWallet['created_at']=Carbon::parse($wallets->created_at)->format('Y-m-d');
        $ArrayWallet['updated_at']=Carbon::parse($wallets->updated_at)->format('Y-m-d');
        return $ArrayWallet;


    }




}
