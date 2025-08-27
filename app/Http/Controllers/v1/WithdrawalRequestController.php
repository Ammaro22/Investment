<?php

namespace App\Http\Controllers\v1;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WithdrawalProcessedNotification;

class WithdrawalRequestController extends BaseController
{

    //USER//

    public function make_request(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'method' => 'required|in:bank,pyramid,western_union,crypto,manual',
            'method_details' => 'nullable|array'
        ]);

        $user = Auth::user();

        if (!$user||$user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بطلب السحب.'], 403);
        }
        $existing = WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['error' => 'لديك طلب سحب معلق حالياً.'], 400);
        }

        $wallet = $user->wallets()->where('wallet_type', 'profits')->first();

        if (!$wallet || $wallet->balance < $request->amount) {
            return response()->json(['error' => 'الرصيد غير كاف.'], 400);
        }

        DB::transaction(function () use ($request, $user, $wallet) {
            $wallet->decrement('balance', $request->amount);

            WithdrawalRequest::create([
                'user_id' => $user->id,
                'amount' => $request->input('amount'),
                'method' => $request->input('method'),
                'method_details' => $request->input('method_details'),
                'status' => 'pending'
            ]);


            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'type' => 'withdrawal',
                'status' => 'pending'
            ]);
        });

        $this->firebaseNotification->sendToUser($user,'make_withdrawal_request');

        return response()->json(['message' => 'تم إرسال طلب السحب بنجاح.']);
    }


    public function getReceipt($id)
    {
        $user = Auth::user();

        if (!$user||$user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بطلب السحب.'], 403);
        }
        $withdraw = WithdrawalRequest::with('user')->findOrFail($id);

        if ($withdraw->user_id !== auth()->id()) {
            abort(403);
        }

        if ($withdraw->status !== 'processed') {
            return response()->json(['error' => 'الإيصال متاح فقط بعد تنفيذ العملية.'], 400);
        }

        return response()->json([
            'receipt_id' => 'WD-' . str_pad($withdraw->id, 6, '0', STR_PAD_LEFT),
            'reference' => $withdraw->transaction_reference,
            'method' => $withdraw->method,
            'method_details' => $withdraw->method_details,
            'amount' => $withdraw->amount,
            'processed_at' => $withdraw->processed_at,
            'user_name' => $withdraw->user->name,
        ]);
    }




    public function getAllMyReceipts()
    {
        $user = Auth::user();
        if (!$user||$user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بطلب السحب.'], 403);
        }
        $requests = WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'processed')
            ->orderByDesc('processed_at')
            ->get()
            ->map(function ($withdraw) {
                return [
                    'receipt_id' => 'WD-' . str_pad($withdraw->id, 6, '0', STR_PAD_LEFT),
                    'reference' => $withdraw->transaction_reference,
                    'amount' => $withdraw->amount,
                    'method' => $withdraw->method,
                    'processed_at' => $withdraw->processed_at,
                    'notes' => $withdraw->admin_notes,
                ];
            });

        return response()->json($requests);
    }



    public function getWithdrawalRequestByStatusForUser(Request $request)
    {
        $user = Auth::user();
        if (!$user||$user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بطلب السحب.'], 403);
        }
        $query = WithdrawalRequest::where('user_id',$user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }



    public function getAllWithdrawalRequestForUser()
    {
        $user = Auth::user();
        if (!$user||$user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بطلب السحب.'], 403);
        }
        $requests = WithdrawalRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function showRequestById($id)
    {
        $user = Auth::user();
        if (!$user || $user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بطلب السحب.'], 403);
        }

        $withdraw = WithdrawalRequest::where('user_id', $user->id)->where('id', $id)->first();

        if (!$withdraw) {
            return response()->json(['error' => 'لا يوجد طلب لهذا المستخدم بهذا المعرف'], 404);
        }

        return response()->json($withdraw);
    }



//ADMIN//


    public function approveAndProcess($id, Request $request)
    {
        $admin = Auth::user();
        if ($admin->role_id !== 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $withdraw = WithdrawalRequest::findOrFail($id);

        if ($withdraw->status !== 'pending') {
            return response()->json(['error' => 'Only pending requests can be approved.'], 400);
        }

        DB::transaction(function () use ($withdraw, $request) {
            $withdraw->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);


            $withdraw->update([
                'status' => 'processed',
                'processed_at' => now(),
                'transaction_reference' => $request->input('transaction_reference')
            ]);

            $platformWallet = Wallet::where('wallet_type', 'platform')->first();

            Transaction::create([
                'user_id' => $withdraw->user_id,
                'wallet_id' => $platformWallet->id,
                'amount' => $withdraw->amount,
                'type' => 'transfer_in',
                'status' => 'completed'
            ]);

            Notification::route('mail', $withdraw->user->email)
                ->notify(new WithdrawalProcessedNotification($withdraw));
        });
        $this->firebaseNotification->sendToUser($withdraw->user,'accept_withdrawal_request');


        return response()->json(['message' => 'Withdrawal approved and processed successfully.']);
    }





    public function index(Request $request)
    {
        $admin = Auth::user();
        if ($admin->role_id !== 1) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $query = WithdrawalRequest::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('method')) {
            $query->where('method', $request->input('method'));
        }

        return response()->json($query->with('user')->latest()->paginate(20));
    }


    public function reject($id, Request $request)
    {
        $admin = Auth::user();
        if ($admin->role_id !== 1) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $withdraw = WithdrawalRequest::findOrFail($id);

        if ($withdraw->status !== 'pending') {
            return response()->json(['error' => 'يمكن فقط رفض الطلبات المعلقة.'], 400);
        }

        DB::transaction(function () use ($withdraw, $request) {
            $wallet = Wallet::where('user_id', $withdraw->user_id)
                ->where('wallet_type', 'profits')->first();

            $wallet->increment('balance', $withdraw->amount);

            $withdraw->update([
                'status' => 'rejected',
                'admin_notes' => $request->input('admin_notes')
            ]);

            Transaction::create([
                'user_id' => $withdraw->user_id,
                'wallet_id' => $wallet->id,
                'amount' => $withdraw->amount,
                'type' => 'refund',
                'status' => 'completed'
            ]);
        });

        return response()->json(['message' => 'تم رفض الطلب وتم استرجاع المبلغ.']);
    }

    public function addInvestmentBalance( Request $request)
    {

        $admin = Auth::user();
        if ($admin->role_id !== 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $userId = $validated['user_id'];
        $amount = $validated['amount'];


        DB::beginTransaction();

            $wallet = Wallet::where('user_id', $userId)
                ->where('wallet_type', 'investment')
                ->first();

            $wallet->balance += $amount;
            $wallet->save();

            $transaction = Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'completed',
                'stripe_payment_id' => null,
                'related_transaction_id' => null
            ]);

            DB::commit();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $wallet
        ]);


    }
}



/*
    public function process($id, Request $request)
    {
        $admin = Auth::user();
        if ($admin->role_id !== 1) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $withdraw = WithdrawalRequest::findOrFail($id);

        if ($withdraw->status !== 'approved') {
            return response()->json(['error' => 'يجب أن يكون الطلب موافق عليه أولاً.'], 400);
        }

        DB::transaction(function () use ($withdraw, $request) {
            $withdraw->update([
                'status' => 'processed',
                'processed_at' => now(),
                'transaction_reference' => $request->input('transaction_reference')
            ]);

            $platformWallet = Wallet::where('wallet_type', 'platform')->first();

            Transaction::create([
                'user_id' => $withdraw->user_id,
                'wallet_id' => $platformWallet->id,
                'amount' => $withdraw->amount,
                'type' => 'transfer_in',
                'status' => 'completed'
            ]);

            $withdraw->user->notify(new WithdrawalProcessedNotification($withdraw));

        });

        return response()->json(['message' => 'تم تنفيذ السحب وتحويل المبلغ لمحفظة المنصة.']);
   }*/




/*  public function approve($id)
  {
      $admin = Auth::user();
      if ($admin->role_id !== 1) {
          return response()->json(['error' => 'غير مصرح'], 403);
      }

      $request = WithdrawalRequest::findOrFail($id);

      if ($request->status !== 'pending') {
          return response()->json(['error' => 'يمكن الموافقة فقط على الطلبات المعلقة.'], 400);
      }

      $request->update([
          'status' => 'approved',
          'approved_at' => now()
      ]);

      return response()->json(['message' => 'تمت الموافقة على طلب السحب.']);
  }*/
