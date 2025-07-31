<?php

namespace App\Jobs;

use App\Http\Controllers\v1\WalletController;
use App\Models\Profit;
use App\Models\Wallet;
use Carbon\Carbon;
use DatabaseLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TransferProfitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('TransferProfitJob started');
        $today=Carbon::today();

        $pendingTransfer=Profit::where('transfer_status','pending')
            ->where('transfer_attempts', '<', 3)
            ->whereDate('scheduled_date','<=',$today)
            ->with(['completedProperty','user'])
            ->get();




        foreach ($pendingTransfer as $pending)
        {
            try {
                  $platformWallet = Wallet::where('wallet_type', 'platform')->first();
                 if (!$platformWallet)
                 {
                  throw new \Exception(trans('messages.not_found'));
                 }

                 if ($platformWallet->balance < $pending->profit_amount)
                 {
                 throw new \Exception(trans('messages.insufficient_balance'));
                 }

                 if($pending->transfer_attempts >0)
                 {
                     sleep(5);
                 }
                $walletController = new WalletController();
                $walletController->transferFromPlatform(new Request([
                'amount' => $pending->profit_amount,
                'user_id' => $pending->user_id
                 ]));

                $pending->update([
                    'transfer_status' => 'completed',
                    'processed_at'=>now()
                ]);

                DatabaseLogger::log('info','transfer to profit wallet',['user_id'=>$pending->user_id,
                    'user_name'=>$pending->user_name]);
               }catch (\Exception $exception) {

                $pending->update([
                    'transfer_attempts' => $pending->transfer_attempts + 1,
                    'transfer_status' => $pending->transfer_attempts >= 2 ? 'failed' : 'pending',
                    'failure_reason' => $exception->getMessage()
                ]);

                DatabaseLogger::log('error','transfer_failed',[
                    'profit_id'=>$pending->id,
                    'user_id'=>$pending->user_id,
                    'error'=>$exception->getMessage()
                ]);
            }
        }
    }

}

