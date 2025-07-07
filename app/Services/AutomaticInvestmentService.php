<?php

namespace App\Services;

use App\Models\AmountInvested;
use App\Models\Wallet;
use App\Models\Reward;
use Illuminate\Http\Request;
use App\Http\Controllers\WalletController;
use App\Models\AutomaticInvestment;
use App\Models\CompletedProperty;
use App\Models\EconomicEvaluation;
use App\Models\Profit;
use App\Models\RewardTransactions;
use App\Models\User;
use App\Models\Investment;
use App\Models\PropertyForInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomaticInvestmentService
{
    protected $preferenceEngine;
    protected $analysisService;
    protected $notificationService;

    public function __construct(
        UserPreferenceEngine $preferenceEngine,
        PropertyAnalysisService $analysisService,
        FirebaseNotificationService $notificationService
    ) {
        $this->preferenceEngine = $preferenceEngine;
        $this->analysisService = $analysisService;
        $this->notificationService = $notificationService;
    }



    public function activateAutomaticInvestment(User $user, $investmentAmount, $investmentMode, $expectedProfitRange, $expectedChanceRange)
    {

        if ($investmentAmount === null) {
            AutomaticInvestment::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'start_date' => now(),
                    'next_investment_date' => now()->addDays(10),
                    'investment_mode' => $investmentMode,
                    'expected_profit_min' => $expectedProfitRange['min'],
                    'expected_profit_max' => $expectedProfitRange['max'],
                    'min_chance_invested' => $expectedChanceRange['min_chance'],
                    'max_chance_invested' => $expectedChanceRange['max_chance'],
                    'investment_amount' => null,
                    'active' => true
                ]
            );

            return [
                'success' => 'تم تفعيل الاستثمار التلقائي بدون تحديد مبلغ محدد',
                'results' => []
            ];
        }

        // التحقق من صحة المدخلات للحالات الأخرى
        if (!is_numeric($investmentAmount) || $investmentAmount < 0 || !$investmentMode || !$expectedProfitRange) {
            return ['error' => 'معلومات الاستثمار غير صحيحة.'];
        }

        $discountRate = $this->getHighestRewardAndUpdate($user->id);
        if ($discountRate > 0) {
            $discountAmount = ($investmentAmount * $discountRate) / 100;
            $investmentAmount -= $discountAmount;
        }

        $properties = $this->getBestProperties($investmentAmount, $investmentMode, $expectedProfitRange, $user);
        if ($properties->isEmpty()) {
            return ['error' => 'لا توجد عقارات مناسبة للاستثمار.'];
        }

        $results = [];
        $walletController = new WalletController();
        $amountPerProperty = $investmentAmount / max(1, $properties->count());
        $totalInvestedAmount = 0;

        foreach ($properties as $property) {
            DB::beginTransaction();

            try {
                $chancePrice = $property->chance_price;
                $totalPropertyPrice = $property->expected_price;
                $maxAllowedInvestment = $totalPropertyPrice * 0.10;

                $alreadyInvested = $property->investment()
                    ->where('user_id', $user->id)
                    ->sum('amount_payed');

                $maxInvestableChances = floor(($maxAllowedInvestment - $alreadyInvested) / $chancePrice);

                $chancesToInvest = max(
                    $expectedChanceRange['min_chance'],
                    min($expectedChanceRange['max_chance'], $maxInvestableChances, $property->number_of_chances)
                );

                if ($chancesToInvest < $expectedChanceRange['min_chance']) {
                    DB::rollBack();
                    continue;
                }

                $newInvestmentAmount = $chancePrice * $chancesToInvest;

                if (($alreadyInvested + $newInvestmentAmount) > $maxAllowedInvestment) {
                    DB::rollBack();
                    $results[] = [
                        'property_id' => $property->id,
                        'error' => 'مجموع استثمارك في هذا العقار تجاوز الحد المسموح (10% من سعر العقار).'
                    ];
                    continue;
                }

                $investmentWallet = $user->wallets()->where('wallet_type', 'investment')->first();
                if (!$investmentWallet || $investmentWallet->balance < $newInvestmentAmount) {
                    DB::rollBack();
                    continue;
                }

                $walletController->transferToPlatform(new Request(['amount' => $newInvestmentAmount]));

                Investment::create([
                    'user_id' => $user->id,
                    'property_for_investment_id' => $property->id,
                    'chance_invested' => $chancesToInvest,
                    'amount_payed' => $newInvestmentAmount,
                    'discount_applied' => 0,
                    'status' => 'completed',
                    'investment_date' => now()
                ]);

                $property->number_of_chances -= $chancesToInvest;
                $property->save();

                $this->updatePropertyProgress($property);
                if ($property->number_of_chances == 0) {
                    $this->handleCompletedProperty($property);
                }

                $this->calculateRewards($user, $newInvestmentAmount);
                DB::commit();

                $this->notificationService->sendToUser($user, 'investment_success');

                $results[] = [
                    'property_id' => $property->id,
                    'chances_invested' => $chancesToInvest,
                    'amount' => $newInvestmentAmount,
                    'message' => 'تم الاستثمار بنجاح'
                ];

                $totalInvestedAmount += $newInvestmentAmount;

            } catch (\Exception $e) {
                DB::rollBack();
                $results[] = [
                    'property_id' => $property->id,
                    'error' => 'فشل في عملية الاستثمار: ' . $e->getMessage()
                ];
            }
        }

        if (empty($results)) {
            return ['message' => 'لم يتم تنفيذ أي استثمار بسبب عدم توفر الشروط'];
        }

        // الحصول على الاستثمار التلقائي السابق إن وجد
        $autoInvestment = AutomaticInvestment::where('user_id', $user->id)->first();

        // حساب المبلغ المتبقي
        if ($autoInvestment && $autoInvestment->investment_amount !== null) {
            // إذا كان هناك مبلغ موجود مسبقاً، نخصم منه المبلغ المستثمر
            $remainingAmount = max(0, $autoInvestment->investment_amount - $totalInvestedAmount);
        } else {
            // إذا لم يكن هناك مبلغ مسبق أو كان null، نستخدم المبلغ الجديد
            $remainingAmount = max(0, $investmentAmount - $totalInvestedAmount);
        }

        // تحديث أو إنشاء الاستثمار التلقائي
        AutomaticInvestment::updateOrCreate(
            ['user_id' => $user->id],
            [
                'start_date' => now(),
                'next_investment_date' => now()->addDays(10),
                'investment_mode' => $investmentMode,
                'expected_profit_min' => $expectedProfitRange['min'],
                'expected_profit_max' => $expectedProfitRange['max'],
                'min_chance_invested' => $expectedChanceRange['min_chance'],
                'max_chance_invested' => $expectedChanceRange['max_chance'],
                'investment_amount' => $remainingAmount,
                'active' => $remainingAmount > 0 || $investmentAmount === 0
            ]
        );

        return [
            'success' => 'تمت معالجة الاستثمار التلقائي',
            'results' => $results,

        ];
    }



    public function getBestProperties($investmentAmount, $investmentMode, $expectedProfitRange, User $user)
    {
        Log::info('بدء البحث عن أفضل العقارات', [
            'investment_amount' => $investmentAmount,
            'investment_mode' => $investmentMode,
            'expected_profit_range' => $expectedProfitRange,
            'user_id' => $user->id
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $properties = PropertyForInvestment::where('investment_mode', $investmentMode)
            ->where('chance_price', '<=', $investmentAmount)
            ->where('is_completed', false)
            ->whereHas('economicEvaluation', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('investment_time', [$startDate, $endDate]);
            })
            ->with(['property', 'indicatorValues.indicator', 'economicEvaluation'])
            ->get();

        Log::info('تم جلب العقارات', ['count' => $properties->count()]);

        $scoredProperties = $properties->map(function ($property) use ($expectedProfitRange, $user) {
            // استبعاد العقارات خارج نطاق الربح المطلوب
            if ($property->profit_percent < $expectedProfitRange['min'] ||
                $property->profit_percent > $expectedProfitRange['max']) {
                Log::info('تم استبعاد العقار بسبب عدم تطابق نطاق الربح', ['property_id' => $property->id]);
                return null;
            }

            $economicEvaluation = $property->economicEvaluation;
            if (!$economicEvaluation) {
                Log::info('تم استبعاد العقار بسبب عدم وجود تقييم اقتصادي', ['property_id' => $property->id]);
                return null;
            }

            // تحليل المؤشرات
            $indicatorValues = $property->indicatorValues;
            $positiveIndicators = 0;
            $totalIndicators = $indicatorValues->count();

            // إذا لم يكن هناك مؤشرات على الإطلاق، يتم استبعاد العقار
            if ($totalIndicators === 0) {
                Log::info('تم استبعاد العقار بسبب عدم وجود مؤشرات', ['property_id' => $property->id]);
                return null;
            }

            foreach ($indicatorValues as $indicatorValue) {
                $indicator = $indicatorValue->indicator;
                $value = $indicatorValue->value;

                if ($value >= $indicator->recommended_min && $value <= $indicator->recommended_max) {
                    $positiveIndicators++;
                }
            }

            // حساب النقاط مع معاقبة العقارات التي لديها مؤشرات أقل
            $indicatorScore = ($positiveIndicators / $totalIndicators) * 50;
            $profitScore = ($property->profit_percent / $expectedProfitRange['max']) * 30;
            $progressScore = $property->progress_percent * 0.2;

            $totalScore = $indicatorScore + $profitScore + $progressScore;

            return [
                'property' => $property,
                'score' => $totalScore,
                'positive_indicators' => $positiveIndicators,
                'total_indicators' => $totalIndicators,
                'profit_percent' => $property->profit_percent
            ];
        })->filter()->sortByDesc('score');

        Log::info('العقارات المفلترة والمصنفة', ['count' => $scoredProperties->count()]);

        if ($scoredProperties->isEmpty()) {
            Log::error('لم يتم العثور على عقارات مناسبة للاستثمار');
            throw new \Exception('عذراً، لم نتمكن من العثور على عقارات استثمارية تناسب معاييرك.');
        }

        $topProperties = $scoredProperties->take(2);

        Log::info('أفضل عقارين مختارين', [
            'properties' => $topProperties->map(function($item) {
                return [
                    'id' => $item['property']->id,
                    'score' => $item['score'],
                    'positive_indicators' => $item['positive_indicators'],
                    'total_indicators' => $item['total_indicators'],
                    'profit_percent' => $item['profit_percent']
                ];
            })
        ]);

        return $topProperties->pluck('property');
    }

    protected function updatePropertyProgress($property)
    {
        $originalChances = $property->getOriginal('number_of_chances') + $property->investment->sum('chance_invested');
        $investedChances = $property->investment->sum('chance_invested');
        $progress = $originalChances > 0 ? round(($investedChances / $originalChances) * 100, 2) : 0;
        $property->update(['progress_percent' => min($progress, 100)]);
    }

    protected function handleCompletedProperty($property)
    {
        $property->update(['is_completed' => true]);

        CompletedProperty::firstOrCreate([
            'property_for_investment_id' => $property->id,
            'property_management' => $property->property_management,
        ]);

        $this->CalculateNetProfit($property->id);
    }

    protected function getHighestRewardAndUpdate($userId)
    {
        $discount = 0;

        $highestReward = RewardTransactions::with('reward')
            ->where('user_id', $userId)
            ->where('number_of_times', '>', 0)
            ->whereHas('reward') // تأكّد من وجود المكافأة فعلاً
            ->orderByDesc(Reward::select('amount_threshold')
                ->whereColumn('rewards.id', 'reward_transactions.reward_id')
                ->limit(1))
            ->first();

        if ($highestReward) {
            Log::info('Found highest reward:', [
                'reward_id' => $highestReward->reward_id,
                'number_of_times' => $highestReward->number_of_times
            ]);

            $highestReward->number_of_times -= 1;

            if ($highestReward->save()) {
                Log::info('Updated number_of_times successfully.', [
                    'number_of_times' => $highestReward->number_of_times
                ]);
                $discount = $highestReward->reward->discount_rate;
            } else {
                Log::error('Failed to update number_of_times.', [
                    'reward_id' => $highestReward->reward_id
                ]);
            }
        } else {
            Log::info('No valid rewards found for the user.');
        }

        return $discount;
    }

    public function CalculateNetProfit($property_id)
    {
        $property = PropertyForInvestment::with('investment')->find($property_id);

        if (!$property || !$property->is_completed) {
            return response()->json(['message' => trans('messages.operation_failed')]);
        }

        $economic = EconomicEvaluation::where('property_for_sale_id', $property_id)->first();

        if (!$economic) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $existingProfits = Profit::whereHas('completedProperty', function ($q) use ($property_id) {
            $q->where('property_for_investment_id', $property_id);
        })->count();

        if ($existingProfits > 0) {
            return response()->json(['message' => trans('messages.profits_already_calculated')]);
        }

        $total_expected_taxes = $economic->total_expected_taxes;
        $profit_percent = $property->profit_percent;
        $renting = $economic->renting_price ?? null;
        $selling = $economic->baying_price;
        $total_chances = $property->number_of_chances + $property->investment->sum('chance_invested');

        foreach ($property->investment as $investment) {
            $user = $investment->user;
            $chance_invested = $investment->chance_invested;
            $person_profit = $chance_invested * $profit_percent;
            $total_profit = $total_chances * $profit_percent;

            if ($total_profit == 0) {
                continue;
            }

            $taxes_of_one_user = ($total_expected_taxes * $chance_invested) / $total_chances;
            $profit_ratio = $person_profit / $total_profit;

            if ($economic->property_management == 'rent') {
                $profit_amount = $renting * $profit_ratio;
            } else {
                $profit_amount = $selling * $profit_ratio;
            }

            $net_profit = $profit_amount - $taxes_of_one_user;

            $this->profit($net_profit, $property_id, $user->id);

            // تعديل استدعاء الإشعار
            $this->notificationService->sendToUser($user, 'profit_calculated');
        }

        return response()->json(['message' => trans('messages.operation_success')]);
    }

    protected function calculateRewards($user, $investmentAmount)
    {
        $amountInvested = AmountInvested::firstOrNew(['user_id' => $user->id]);
        $amountInvested->amount_invested += $investmentAmount;
        $amountInvested->save();

        $totalInvested = $amountInvested->amount_invested;

        $rewards = Reward::where('amount_threshold', '<=', $totalInvested)->get();

        if ($rewards->isEmpty()) {
            Log::info('No rewards available for current total invested.', [
                'user_id' => $user->id,
                'total_invested' => $totalInvested
            ]);
        }

        foreach ($rewards as $reward) {
            $existingReward = RewardTransactions::where('user_id', $user->id)
                ->where('reward_id', $reward->id)
                ->first();

            if (!$existingReward) {
                $rewardAmount = ($totalInvested * $reward->percentage) / 100;

                DB::transaction(function () use ($user, $reward, $rewardAmount) {
                    $mainWallet = Wallet::where('wallet_type', 'platform')->lockForUpdate()->first();
                    $profitWallet = $user->wallets()->where('wallet_type', 'profits')->lockForUpdate()->first();

                    if (!$mainWallet || !$profitWallet) {
                        Log::error('Wallet(s) missing during reward distribution.', [
                            'user_id' => $user->id,
                            'mainWallet_found' => !!$mainWallet,
                            'profitWallet_found' => !!$profitWallet,
                        ]);
                        return;
                    }

                    if ($mainWallet->balance >= $rewardAmount) {
                        $mainWallet->balance -= $rewardAmount;
                        $mainWallet->save();

                        $profitWallet->balance += $rewardAmount;
                        $profitWallet->save();

                        RewardTransactions::create([
                            'user_id' => $user->id,
                            'reward_id' => $reward->id,
                            'amount_profit' => $rewardAmount,
                            'state' => 'completed',
                            'number_of_times' => $reward->number_of_times,
                        ]);

                        // تعديل استدعاء الإشعار
                        $this->notificationService->sendToUser($user, 'reward_distributed');

                        Log::info('Reward transferred successfully.', [
                            'user_id' => $user->id,
                            'reward_id' => $reward->id,
                            'reward_amount' => $rewardAmount
                        ]);
                    } else {
                        Log::warning('Main wallet has insufficient balance for reward.', [
                            'user_id' => $user->id,
                            'required' => $rewardAmount,
                            'available' => $mainWallet->balance,
                            'reward_id' => $reward->id
                        ]);
                    }
                });
            }
        }
    }

    public function profit($net_profit, $property_id, $user_id)
    {
        $completedProperty = CompletedProperty::where('property_for_investment_id', $property_id)->first();

        if (!$completedProperty) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $existingProfit = Profit::where('completed_property_id', $completedProperty->id)->where('user_id', $user_id)->first();

        if ($existingProfit) {
            return response()->json(['message' => trans('messages.profit_already_exists')]);
        }

        $economic = EconomicEvaluation::where('property_for_sale_id', $property_id)->first();

        if (!$economic->incoming_time) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        Profit::create([
            'completed_property_id' => $completedProperty->id,
            'user_id' => $user_id,
            'profit_amount' => $net_profit,
            'scheduled_date' => $economic->incoming_time,
            'transfer_status' => 'pending',
            'transfer_attempts' => 0,
            'processed_at' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'success']);
    }

}
