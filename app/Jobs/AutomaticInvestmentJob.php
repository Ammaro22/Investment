<?php

namespace App\Jobs;

use App\Models\AutomaticInvestment;
use App\Services\AutomaticInvestmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomaticInvestmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $investmentService;

    public function __construct(AutomaticInvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

//    public function handle()
//    {
//        $automaticInvestments = AutomaticInvestment::where('active', true)
//            ->where('next_investment_date', '<=', now())
//            ->with('user')
//            ->get();
//
//        if ($automaticInvestments->isEmpty()) {
//            Log::info('No automatic investments due for processing.');
//            return;
//        }
//
//        foreach ($automaticInvestments as $investment) {
//            try {
//                $result = $this->investmentService->activateAutomaticInvestment(
//                    $investment->user,
//                    $investment->investment_amount,
//                    $investment->investment_mode,
//                    [
//                        'min' => $investment->expected_profit_min,
//                        'max' => $investment->expected_profit_max
//                    ],
//                    [
//                        'min_chance' => $investment->min_chance_invested,
//                        'max_chance' => $investment->max_chance_invested
//                    ]
//                );
//
//                if (isset($result['error'])) {
//                    Log::error('Automatic investment failed for user: ' . $investment->user_id, ['error' => $result['error']]);
//                    continue;
//                }
//
//                $investment->update([
//                    'start_date' => now(),
//                    'next_investment_date' => now()->addDays(10)
//                ]);
//
//                Log::info('Automatic investment processed successfully for user: ' . $investment->user_id, [
//                    'results' => $result['results'] ?? []
//                ]);
//
//            } catch (\Exception $e) {
//                Log::error('Error processing automatic investment for user: ' . $investment->user_id, [
//                    'error' => $e->getMessage(),
//                    'trace' => $e->getTraceAsString()
//                ]);
//            }
//        }
//    }

    public function handle()
    {
        $automaticInvestments = AutomaticInvestment::where('active', true)
            ->where('next_investment_date', '<=', now())
            ->with('user')
            ->get();

        if ($automaticInvestments->isEmpty()) {
            Log::info('No automatic investments due for processing.');
            return;
        }

        foreach ($automaticInvestments as $investment) {
            try {
                $result = $this->investmentService->activateAutomaticInvestment(
                    $investment->user,
                    $investment->investment_amount,
                    $investment->investment_mode,
                    [
                        'min' => $investment->expected_profit_min,
                        'max' => $investment->expected_profit_max
                    ],
                    [
                        'min_chance' => $investment->min_chance_invested,
                        'max_chance' => $investment->max_chance_invested
                    ]
                );

                $investment->update([
                    'start_date' => now(),
                    'next_investment_date' => now()->addDays(10)
                ]);

                if (isset($result['error'])) {
                    Log::error('Automatic investment failed for user: ' . $investment->user_id, ['error' => $result['error']]);
                    continue;
                }

                Log::info('Automatic investment processed successfully for user: ' . $investment->user_id, [
                    'results' => $result['results'] ?? []
                ]);

            } catch (\Exception $e) {
                Log::error('Error processing automatic investment for user: ' . $investment->user_id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // تحديث تاريخ الاستثمار القادم حتى في حالة وجود استثناء
                $investment->update([
                    'start_date' => now(),
                    'next_investment_date' => now()->addDays(10)
                ]);
            }
        }
    }
}
