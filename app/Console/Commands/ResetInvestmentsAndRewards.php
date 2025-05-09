<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AmountInvested;
use App\Models\RewardTransactions;

class ResetInvestmentsAndRewards extends Command
{
    protected $signature = 'reset:investments-and-rewards';
    protected $description = 'Reset amount_invested and delete all reward_transactions';

    public function handle()
    {
        // تصفير قيمة amount_invested لكل المستخدمين
        AmountInvested::query()->update(['amount_invested' => 0]);

        // حذف جميع البيانات من جدول reward_transactions
        RewardTransactions::query()->delete();

        $this->info('مهمة تصفير الاستثمارات وحذف المعاملات تمت بنجاح.');
    }
}
