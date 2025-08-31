<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers;
use App\Models\AmountInvested;
use App\Models\CompletedProperty;
use App\Models\EconomicEvaluation;
use App\Models\Investment;
use App\Models\InvestmentCertificate;
use App\Models\Profit;
use App\Models\Property_for_sale;
use App\Models\PropertyForInvestment;
use App\Models\Reward;
use App\Models\RewardTransactions;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\FirebaseNotificationService;
use App\Services\FireStoreTokenService;
use App\Services\PropertyAnalysisService;
use App\Services\UserPreferenceEngine;
use Carbon\Carbon;
use DatabaseLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InvestmentController extends BaseController
{
    protected $walletController;

    public function __construct(
        FirebaseNotificationService $firebaseNotification,
        FireStoreTokenService $fireStoreTokenService,
        WalletController $walletController
    ) {
        parent::__construct($firebaseNotification, $fireStoreTokenService);
        $this->walletController = $walletController;
    }

    public function ShowProperty()
    {
        $user = Auth::guard('api')->user();

        $inferenceEngine = new UserPreferenceEngine();
        $propertyAnalyzes = new PropertyAnalysisService();

        $property = PropertyForInvestment::with(['property', 'property.economicEvaluation'])->paginate(5);

        $properties = $property->map(function ($item) use ($user, $inferenceEngine, $propertyAnalyzes) {
            if (isset($item->property)) {
                unset($item->property->economicEvaluation);
            }

            $rearrangedItem = $this->re_arrange($item);
            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }


            $economicEvaluation = $item->property->economicEvaluation ?? null;
            $analyze = $economicEvaluation ? $propertyAnalyzes->analyze($economicEvaluation) : null;


            $userPreference = ($user && $item->property)
                ? $inferenceEngine->getRecommendationForInvestment($user, $item->property)
                : [];

            return array_merge([
                'property_for_investment_id' => $item->id,
            ], $rearrangedItem, [
                'property_images' => $item->property->Property_image ?? [],
                'economic_advice' => $analyze,
                'user_advice' => $userPreference,
            ]);
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $properties,
                'pagination' => [
                    'current_page' => $property->currentPage(),
                    'last_page' => $property->lastPage(),
                    'per_page' => $property->perPage(),
                    'total' => $property->total(),
                    'next_page_url' => $property->nextPageUrl(),
                    'prev_page_url' => $property->previousPageUrl(),
                ]
            ]
        ]);
    }


    public function ShowPropertyByType(Request $request)
    {
        $user = Auth::guard('api')->user();

        $inferenceEngine = new UserPreferenceEngine();
        $propertyAnalyzes = new PropertyAnalysisService();

        $validator = Validator::make($request->all(), [
            'property_type' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $property_type = $request->property_type;

        $property = PropertyForInvestment::with(['property', 'property.economicEvaluation'])
            ->whereHas('property', function ($query) use ($property_type) {
                $query->where('property_type', $property_type);
            })
            ->paginate(5);

        $properties = $property->map(function ($item) use ($user, $inferenceEngine, $propertyAnalyzes) {
            if (isset($item->property)) {
                unset($item->property->economicEvaluation);
            }
            $rearrangedItem = $this->re_arrange($item);
            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }

            $economicEvaluation = $item->property->economicEvaluation;
            $analyze = $economicEvaluation ? $propertyAnalyzes->analyze($economicEvaluation) : null;

            $userPreference = $user && $item->property
                ? $inferenceEngine->getRecommendationForInvestment($user, $item->property)
                : [];

            $rearrangedItem = array_merge([
                'property_for_investment_id' => $item->id],
                $rearrangedItem, [
                    'property_images' => $item->property->Property_image ?? [],
                    'economic_advice' => $analyze,
                    'user_advice' => $userPreference,
                ]);


            if ($user) {
                DatabaseLogger::log('info', 'search by PropertyType', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'property_id' => $item->id,
                ]);
            }

            return $rearrangedItem;
        });

        $data = [
            'message' => trans('messages.properties_found'),
            'data' => [
                'properties' => $properties,
                'pagination' => [
                    'current_page' => $property->currentPage(),
                    'last_page' => $property->lastPage(),
                    'per_page' => $property->perPage(),
                    'total' => $property->total(),
                    'next_page_url' => $property->nextPageUrl(),
                    'prev_page_url' => $property->previousPageUrl(),
                ]
            ]
        ];

        return response()->json($data);
    }

    public function ShowPropertyByInvestmentType(Request $request)
    {


        $user = Auth::guard('api')->user();

        $inferenceEngine = new UserPreferenceEngine();
        $propertyAnalyzes = new PropertyAnalysisService();


        $validator = Validator::make($request->all(), [

            'investment_mode' => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }


        $property = PropertyForInvestment::with(['property', 'property.economicEvaluation'])->where('investment_mode', $request->investment_mode)->paginate(5);

//        if ($property->isEmpty()) {
//            return response()->json(['message' => trans('messages.no_properties_found')]);
//        }


        $properties = $property->map(function ($item) use ($user, $inferenceEngine, $propertyAnalyzes) {
            if (isset($item->property)) {
                unset($item->property->economicEvaluation);
            }
            $rearrangedItem = $this->re_arrange($item);
            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }

            $economicEvaluation = $item->property->economicEvaluation;
            $analyze = $economicEvaluation ? $propertyAnalyzes->analyze($economicEvaluation) : null;

            $userPreference = $user && $item->property
                ? $inferenceEngine->getRecommendationForInvestment($user, $item->property)
                : [];

            $rearrangedItem = array_merge([
                'property_for_investment_id' => $item->id],
                $rearrangedItem, [
                    'property_images' => $item->property->Property_image ?? [],
                    'economic_advice' => $analyze,
                    'user_advice' => $userPreference,
                ]);

            DatabaseLogger::log('info','search by investmentMode',[
                'user_id'=>$user->id,
                'user_name'=>$user->name,
                'property_id'=>$item->id,

            ]);
            return $rearrangedItem;

        });


        return response()->json([
            'message' => trans('messages.properties_found'),
            'data' => [
                'properties' => $properties,
                'pagination' => [
                    'current_page' => $property->currentPage(),
                    'last_page' => $property->lastPage(),
                    'per_page' => $property->perPage(),
                    'total' => $property->total(),
                    'next_page_url' => $property->nextPageUrl(),
                    'prev_page_url' => $property->previousPageUrl(),
                ]
            ]
        ]);

    }

    public function ShowPropertyById($PropertyId)
    {
        $user = Auth::guard('api')->user();
        $inferenceEngine = new UserPreferenceEngine();
        $propertyAnalyzes = new PropertyAnalysisService();

        $property = PropertyForInvestment::with(['property', 'property.economicEvaluation'])->find($PropertyId);

        if (!$property) {
            return response()->json(['message' => trans('messages.operation_failed')]);
        }

        $economicEvaluation = $property->property->economicEvaluation ?? null;

        $analyze = $economicEvaluation
            ? $propertyAnalyzes->analyze($economicEvaluation)
            : null;

        $userPreference = ($user && $property->property)
            ? $inferenceEngine->getRecommendationForInvestment($user, $property->property)
            : [];

        if (isset($property->property)) {
            unset($property->property->economicEvaluation);
        }
        $formattedData = $this->re_arrange($property);

        unset($formattedData['property_investment']);
        unset($formattedData['created_at']);
        unset($formattedData['updated_at']);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $formattedData,
            'economic_advice' => $analyze,
            'user_advice' => $userPreference
        ]);
    }


    /*سيناريو الاستثمار*/

    public function invest(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $validator = Validator::make($request->all(), [
            'chance_invested' => 'required|integer',
            'property_for_investment_id' => 'required|exists:property_for_investment,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $property = PropertyForInvestment::with('investment')->find($request->property_for_investment_id);

        if (!$property) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $originalChances = $property->getOriginal('number_of_chances');
        $totalPropertyPrice = $property->expected_price;
        $chancePrice = $property->chance_price;


        $totalInvestedAmount = $property->investment()
            ->where('user_id', $user->id)
            ->sum('amount_payed');

        $newInvestmentAmount = $chancePrice * $request->chance_invested;

        $maxAllowedInvestment = $totalPropertyPrice * 0.10;

        if ($totalInvestedAmount + $newInvestmentAmount > $maxAllowedInvestment) {
            return response()->json(['message' => trans('messages.max_investment_reached')]);
        }
        if ($property->number_of_chances < $request->chance_invested) {
            return response()->json(['message' => trans('messages.no_chance_available')]);
        }
        $discount = $this->getHighestRewardAndUpdate($user->id);

        $amount = $property->chance_price * $request->chance_invested;
        if ($discount > 0) {
            $amount -= ($amount * ($discount / 100));
        }

        $investmentWallet = $user->wallets()->where('wallet_type', 'investment')->first();
        if (!$investmentWallet || $investmentWallet->balance < $amount) {
            return response()->json(['message' => trans('messages.insufficient_balance')], 422);
        }

        $this->walletController->transferToPlatform(new Request(['amount' => $amount]));

        $hasDeputization = $user->deputization()->exists();
        $acceptableValue = $hasDeputization ? 1 : 0;

        $d = Investment::create([
            'user_id' => $user->id,
            'property_for_investment_id' => $property->id,
            'chance_invested' => $request->chance_invested,
            'amount_payed' => $amount,
            'Acceptable' => $acceptableValue // إضافة قيمة Acceptable بناءً على وجود الوكالة
        ]);

        $investmentId = $d->id;

        $this->createInvestmentCertificate($investmentId);

        $property->number_of_chances -= $request->chance_invested;
        $property->save();
        $property->refresh();

        $totalChances = $originalChances;
        $investedChances = $property->investment->sum('chance_invested');
        $progress = $totalChances > 0 ? round(($investedChances / $totalChances) * 100, 2) : 0;
        $progress = min($progress, 100);
        $property->update(['progress_percent' => $progress]);

        $isCompleted = $this->isComplete($property->property_id);

        $existing = CompletedProperty::where('property_for_investment_id', $property->id)->first();

        if (!$existing && $isCompleted) {
            CompletedProperty::create([
                'property_for_investment_id' => $property->id,
                'property_management' => $property->property_management,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        if ($isCompleted) {
            $this->CalculateNetProfit($property->id);
        }
        $this->calculateRewards($user, $amount);
        $this->firebaseNotification->sendToUser($user,'investment_process');
        DatabaseLogger::log('info','invested',[
            'user_id'=>$user->id,
            'user_name'=>$user->name,
            'property_id'=>$property->id
        ]);

        return response()->json(['message' => trans('messages.operation_success')]);
    }

    /*عرض العقارات التي استثمرها المستخدم مع تفاصيلها*/

    public function showPropertyInvestedByUser()
    {
        $user = auth()->user();

        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $property_invested = Investment::with('property_invested')->where('user_id', $user->id)->paginate(5);


//        if ($property_invested->isEmpty()) {
//            return response()->json(['message' => trans('messages.not_found')]);
//        }

        $investments = $property_invested->map(function ($item) {
            $main = collect($item->toArray())->except('property_invested');

            $related = collect($item->property_invested)->except('property_management', 'property_id');
            $related2 = collect($item->property_invested->property)->except('legal_check', 'expert_check', 'accept', 'user_id', 'price');
            $re_arrange = $main->merge($related)->merge($related2);

            $created_at = Carbon::parse($re_arrange->pull('created_at'))->format('Y-m-d');
            $updated_at = Carbon::parse($re_arrange->pull('updated_at'))->format('Y-m-d');

            $rearrangedItem = $re_arrange->put('created_at', $created_at)->put('updated_at', $updated_at);


            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }
            $rearrangedItem = array_merge(
                $rearrangedItem, [
                'property_images' => $item->property_invested->property->Property_image]);

            return $rearrangedItem;

        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $investments,
                'pagination' => [
                    'current_page' => $property_invested->currentPage(),
                    'last_page' => $property_invested->lastPage(),
                    'per_page' => $property_invested->perPage(),
                    'total' => $property_invested->total(),
                    'next_page_url' => $property_invested->nextPageUrl(),
                    'prev_page_url' => $property_invested->previousPageUrl(),
                ]
            ]
        ]);


    }

    /*لواجهة المحفظة تبع الاستثمار */

    public function ShowListOfUserInvestment()
    {

        $user = auth()->user();

        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $investments = Investment::with('property_invested.property')->where('user_id', $user->id)->where('Acceptable', 1)->paginate(5);

//        if ($investments->isEmpty()) {
//            return response()->json(['message' => trans('messages.not_found')]);
//        }

        $listOfInvestment = $investments->map(function ($investment) {
            $propertyForSaleInfo = $investment->property_invested->property ?? null;

            return [
                'id' => $investment->id,
                'user_id' => $investment->user_id,
                'property_for_investment_id' => $investment->property_for_investment_id,
                'amount_payed' => $investment->amount_payed,
                'chance_invested' => $investment->chance_invested,
                'property_type' => $propertyForSaleInfo?->property_type,
                'exact_position' => $propertyForSaleInfo?->exact_position,
                'created_at' => $investment->created_at->format('Y-m-d'),
                'updated_at' => $investment->updated_at->format('Y-m-d'),
            ];
        });


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $listOfInvestment,
                'pagination' => [
                    'current_page' => $investments->currentPage(),
                    'last_page' => $investments->lastPage(),
                    'per_page' => $investments->perPage(),
                    'total' => $investments->total(),
                    'next_page_url' => $investments->nextPageUrl(),
                    'prev_page_url' => $investments->previousPageUrl(),
                ]
            ]
        ]);

    }


    public function ShowListOfUserInvestmentByInvestMode(Request $request)
    {


        $user = auth()->user();

        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $validator = Validator::make($request->all(), [
            'investment_mode' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $investment_mode = $request->input('investment_mode');

        $investments = Investment::with('property_invested.property')->where('user_id', $user->id)->whereHas('property_invested', function ($query) use ($investment_mode) {
            $query->where('investment_mode', $investment_mode);
        })->paginate(5);


//        if ($investments->isEmpty()) {
//            return response()->json(['message' => trans('messages.not_found')]);
//        }

        $listOfInvestment = $investments->map(function ($investment) {
            $propertyForSaleInfo = $investment->property_invested->property ?? null;


            return [
                'id' => $investment->id,
                'user_id' => $investment->user_id,
                'property_for_investment_id' => $investment->property_for_investment_id,
                'amount_payed' => $investment->amount_payed,
                'chance_invested' => $investment->chance_invested,
                'property_type' => $propertyForSaleInfo?->property_type,
                'exact_position' => $propertyForSaleInfo?->exact_position,
                'created_at' => $investment->created_at->format('Y-m-d'),
                'updated_at' => $investment->updated_at->format('Y-m-d'),
            ];

        });




        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $listOfInvestment,
                'pagination' => [
                    'current_page' => $investments->currentPage(),
                    'last_page' => $investments->lastPage(),
                    'per_page' => $investments->perPage(),
                    'total' => $investments->total(),
                    'next_page_url' => $investments->nextPageUrl(),
                    'prev_page_url' => $investments->previousPageUrl(),
                ]
            ]
        ]);

    }


    public function ShowListOfUserProfitByInvestMode(Request $request)
    {

        $user = auth()->user();

        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $validator = Validator::make($request->all(), [
            'investment_mode' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $investment_mode = $request->input('investment_mode');

        $profits = Profit::with('completedProperty.property.property')->where('user_id', $user->id)
            ->whereHas('completedProperty.property', function ($query) use ($investment_mode) {
                $query->where('investment_mode', $investment_mode);
            })->paginate(5);


//        if ($profits->isEmpty()) {
//            return response()->json(['message' => trans('messages.not_found')]);
//        }

        $listOfProfits = $profits->map(function ($profit) {
            $propertyInfo = $profit->completedProperty->property->property ?? null;
            return [
                'id' => $profit->id,
                'completed_property_id' => $profit->completed_property_id,
                'user_id' => $profit->user_id,
                'profit_amount' => $profit->profit_amount,
                'property_type' => $propertyInfo?->property_type,
                'exact_position' => $propertyInfo?->exact_position,
                'scheduled_date' => $profit->scheduled_date,
                'transfer_status' => $profit->transfer_status
            ];
        });


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $listOfProfits,
                'pagination' => [
                    'current_page' => $profits->currentPage(),
                    'last_page' => $profits->lastPage(),
                    'per_page' => $profits->perPage(),
                    'total' => $profits->total(),
                    'next_page_url' => $profits->nextPageUrl(),
                    'prev_page_url' => $profits->previousPageUrl(),
                ]
            ]
        ]);

    }




    public function ShowListOfUserProfit()
    {
        $user = auth()->user();
        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $profits = Profit::with(['completedProperty.property.property'])
            ->where('user_id', $user->id)
            ->paginate(5);

//        if ($profits->isEmpty()) {
//            return response()->json(['message' => trans('messages.not_found')]);
//        }

        $listOfProfits = $profits->map(function ($profit) {
            $propertyInfo = $profit->completedProperty->property->property ?? null;

            return [
                'id' => $profit->id,
                'completed_property_id' => $profit->completed_property_id,
                'user_id' => $profit->user_id,
                'profit_amount' => $profit->profit_amount,
                'property_type' => $propertyInfo?->property_type,
                'exact_position' => $propertyInfo?->exact_position,
                'scheduled_date' => $profit->scheduled_date,
                'transfer_status' => $profit->transfer_status,
            ];
        });
        $rewards = RewardTransactions::with('reward') // استخدام العلاقة لجلب بيانات الجائزة
        ->where('user_id', $user->id)
            ->get();

        $totalRewards = $rewards->sum('amount_profit'); // إجمالي الجوائز

        $rewardsWithLevels = $rewards->map(function ($rewardTransaction) {
            return [
                'reward_id' => $rewardTransaction->reward_id,
                'amount_profit' => $rewardTransaction->amount_profit,
                'level' => $rewardTransaction->reward->level ?? null,
            ];
        });
        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'profits' => $listOfProfits,
                'rewards' => $rewards
            ],
            'pagination' => [
                'current_page' => $profits->currentPage(),
                'last_page' => $profits->lastPage(),
                'per_page' => $profits->perPage(),
                'total' => $profits->total(),
                'next_page_url' => $profits->nextPageUrl(),
                'prev_page_url' => $profits->previousPageUrl(),
            ]
        ]);
    }

    /*للنسبة في portfolio*/

    public function ShowPercentageOfInvestments()
    {

        $user = auth()->user();

        $userRole = $user->role_id;

        if (!$user || $userRole != 2) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }

        $Wallet = Wallet::where('user_id', $user->id)->where('wallet_type', 'investment')->first();

        if (!$Wallet) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $Investment = Investment::where('user_id', $user->id)->get();

        $totalIn = Transaction::where('wallet_id', $Wallet->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $amount_payed = $Investment->sum('amount_payed');

        $percentage = $totalIn > 0 ? round(($amount_payed / $totalIn) * 100) : 0;


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => ['percentage' => $percentage]
        ]);
    }


    /* call within invest function*/

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
        }

        return response()->json(['message' => trans('messages.operation_success')]);
    }


    /* call within CalculateNetProfit function*/

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


    /* call within invest function*/
    public function isComplete($property_id)
    {
        if (!$property_id) {
            return false;
        }

        $investment = PropertyForInvestment::where('property_id', $property_id)->first();
        if (!$investment) {
            return false;
        }

        if ($investment->number_of_chances == 0) {
            $investment->update(['is_completed' => true]);
            return true;
        }

        return false;
    }


    public function re_arrange($property)
    {

        $main = collect($property->toArray())->except('property', 'property_id', 'property_management');

        $related = collect($property->property)->except(['legal_check', 'expert_check', 'accept', 'user_id', 'price', 'economicEvaluation']);

        $re_arrange = $main->merge($related);

        $created_at = Carbon::parse($re_arrange->pull('created_at'))->format('Y-m-d');
        $updated_at = Carbon::parse($re_arrange->pull('updated_at'))->format('Y-m-d');

        return $re_arrange->put('created_at', $created_at)->put('updated_at', $updated_at);

    }


    public function Format_timeStamp_Map($type)
    {

        $ArrayWallet = $type->toArray();
        $ArrayWallet['created_at'] = Carbon::parse($type->created_at)->format('Y-m-d');
        $ArrayWallet['updated_at'] = Carbon::parse($type->updated_at)->format('Y-m-d');
        return $ArrayWallet;


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


    public function getEvaluationByProperty($property_id)
    {
        $user = auth()->user();
        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        $property = Property_for_sale::find($property_id);
        if (!$property) {
            return response()->json(['message' => 'Property not found.'], 404);
        }

        $evaluation = EconomicEvaluation::with('indicatorValues.indicator')
            ->where('property_for_sale_id', $property_id)->orderBy('created_at', 'desc')
            ->first();

        if (!$evaluation) {
            return response()->json(['message' => 'No evaluation found for this property.'], 404);
        }

        $filteredIndicatorValues = $evaluation->indicatorValues->map(function ($item) {
            return [
                'id' => $item->id,
                'property_id' => $item->property_id,
                'economic_evaluation_id' => $item->economic_evaluation_id,
                'indicator_id' => $item->indicator_id,
                'value' => $item->value,
            ];
        });

        $data = [
            'id' => $evaluation->id,
            'property_for_sale_id' => $evaluation->property_for_sale_id,
            'number_of_chances' => $evaluation->number_of_chances,
            'profit_percent' => $evaluation->profit_percent,
            'expected_price' => $evaluation->expected_price,
            'buying_price' => $evaluation->buying_price,
            'renting_price' => $evaluation->renting_price,
            'total_expected_taxes' => $evaluation->total_expected_taxes,
            'chance_price' => $evaluation->chance_price,
            'investment_time' => $evaluation->investment_time,
            'incoming_time' => $evaluation->incoming_time,
            'investment_mode' => $evaluation->investment_mode,
            'property_management' => $evaluation->property_management,
            'agreed_negotiation_id' => $evaluation->agreed_negotiation_id,
            'indicator_values' => $filteredIndicatorValues,
        ];

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $data,
        ]);

    }


    /*تحويل المال من محفظة الارباح الخاصة باليوزر الى محفظة الاستثمار*/

    public function transferToInvestment(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $profitWallet = $user->wallets()->where('wallet_type', 'profits')->first();
        $investmentWallet = $user->wallets()->where('wallet_type', 'investment')->first();

        if (!$profitWallet || !$investmentWallet) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        if ($profitWallet->balance <= $request->amount) {
            return response()->json(['message' => trans('stripe.Insufficient_balance')]);
        }


        $profitWallet->balance -= $request->amount;


        $investmentWallet->balance += $request->amount;


        $profitWallet->save();
        $investmentWallet->save();


        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $profitWallet->id,
            'amount' => $request->amount,
            'type' => 'transfer_in',
            'status' => 'completed',
        ]);

        $this->firebaseNotification->sendToUser($user,'transferFromProfitToInvestment');

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'new_profit_balance' => $profitWallet->balance,
                'new_investment_balance' => $investmentWallet->balance
            ],
        ]);

    }

    public function getPropertiesByInvestmentMode(Request $request)
    {
        $investmentMode = $request->input('investment_mode');
        $userId = auth()->id();

        $properties = PropertyForInvestment::with([
            'property',
            'investment.user',
            'completedProperty.profits' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])
            ->where('investment_mode', $investmentMode)
            ->whereHas('investment', function($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('Acceptable', 1);
            })
            ->get()
            ->map(function ($propertyInvestment) use ($userId) {
                $property = $propertyInvestment->property;
                $chance = $propertyInvestment->investment;
                $investments = $propertyInvestment->investment;
                $totalAmountPaid = $investments->sum('amount_payed');
                $totalChanceInvested = $investments->sum('chance_invested');

                $investorCount = $propertyInvestment->investment->count();

                // جمع الأرباح بشكل صحيح
                $userProfit = 0;
                foreach ($propertyInvestment->completedProperty as $completed) {
                    $userProfit += $completed->profits
                        ->where('user_id', $userId)
                        ->sum('profit_amount');
                }

                $location = trim(($property->state ?? 'Unknown') . ', ' . ($property->exact_position ?? 'Unknown'), ', ');

                return [
                    'id' => $propertyInvestment->id,
                    'property_name' => $property->property_type ?? 'Unknown',
                    'property_location' => $location,
                    'profit_percent' => $propertyInvestment->profit_percent ?? 0,
                    'investment_start_time' => $propertyInvestment->created_at->format('Y-m-d H:i:s'),
                    'investment_end_time' => $propertyInvestment->incoming_time ?? null,
                    'amount_payed' => $totalAmountPaid,
                    'chance_invested' => $totalChanceInvested,
                    'investor_count' => $investorCount,
                    'user_profit' => $userProfit,
                    'is_completed' => $propertyInvestment->is_completed
                ];
            });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $properties
        ]);
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

    public function createInvestmentCertificate($investment_id)
    {

        $investment = Investment::with('user', 'property_invested.property')->find($investment_id);

        if (!$investment) {
            return response()->json(['message' => trans('messages.investment_not_found')], 404);
        }

        $property = $investment->property_invested->property;

        $certificate = InvestmentCertificate::create([
            'investment_id' => $investment_id,
            'user_id' => $investment->user->id,
            'property_Location' => $property->state . ', ' . $property->exact_position,
            'number_chance' => $investment->chance_invested,
        ]);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $certificate,
        ], 201);
    }




    public function showInvestmentPropertiesOptimized()
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $properties = PropertyForInvestment::with(['property:id,property_type,state,exact_position'])
            ->withCount('completedProperty')
            ->withSum('investment', 'amount_payed')
            ->paginate(6);

        $transformedProperties = $properties->map(function($property) {
            return [
                'property_id' => $property->id,
                'property_title' => $property->property->property_type ?? 'N/A',
                'property_location' => $property->property->state ?? 'N/A',
                'total_chances' => $property->number_of_chances,
                'is_completed' => $property->is_completed ? 1 : 0, // هنا التعديل
                'total_invested' => $property->investment_sum_amount_payed ?? 0,
                'chance_price' => $property->chance_price,
                'progress_percent' => $property->progress_percent
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $transformedProperties,
                'pagination' => [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                    'next_page_url' => $properties->nextPageUrl(),
                    'prev_page_url' => $properties->previousPageUrl(),
                ]
            ]
        ]);
    }
}



























//    public function ShowProperty()
//    {
//
//        $property = PropertyForInvestment::with('property')->paginate(5);
//
//
//        $properties = $property->map(function ($item) {
//
//            $rearrangedItem = $this->re_arrange($item);
//            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
//                $rearrangedItem = $rearrangedItem->toArray();
//            }
//            $rearrangedItem = array_merge([
//                'property_for_investment_id' => $item->id],
//                $rearrangedItem, [
//                 'property_images'=>$item->property->Property_image]);
//
//            return $rearrangedItem;
//        });
//
//
//        return response()->json([
//            'message' => trans('messages.operation_success'),
//            'data' => [
//                'properties'=>$properties,
//                'pagination' => [
//                    'current_page' => $property->currentPage(),
//                    'last_page' => $property->lastPage(),
//                    'per_page' => $property->perPage(),
//                    'total' => $property->total(),
//                    'next_page_url' => $property->nextPageUrl(),
//                    'prev_page_url' => $property->previousPageUrl(),
//                ]
//        ]
//        ]);
//
//
//    }



//    public function ShowListOfUserProfit()
//    {
//
//        $user = auth()->user();
//
//        $userRole = $user->role_id;
//
//        if (!$user || $userRole != 2) {
//            return response()->json(['message' => trans('messages.unauthorized')]);
//        }
//
//        $profits = Profit::with('completedProperty.property.property')->where('user_id', $user->id)->paginate(5);
//
//
//        if ($profits->isEmpty()) {
//            return response()->json(['message' => trans('messages.not_found')]);
//        }
//
//        $listOfProfits = $profits->map(function ($profit) {
//            $propertyInfo = $profit->completedProperty->property->property ?? null;
//            return [
//                'id' => $profit->id,
//                'completed_property_id' => $profit->completed_property_id,
//                'user_id' => $profit->user_id,
//                'profit_amount' => $profit->profit_amount,
//                'property_type' => $propertyInfo?->property_type,
//                'exact_position' => $propertyInfo?->exact_position,
//                'scheduled_date' => $profit->scheduled_date,
//                'transfer_status' => $profit->transfer_status
//            ];
//        });
//
//
//        return response()->json([
//            'message' => trans('messages.operation_success'),
//            'data' => [
//                'properties' => $listOfProfits,
//                'pagination' => [
//                    'current_page' => $profits->currentPage(),
//                    'last_page' => $profits->lastPage(),
//                    'per_page' => $profits->perPage(),
//                    'total' => $profits->total(),
//                    'next_page_url' => $profits->nextPageUrl(),
//                    'prev_page_url' => $profits->previousPageUrl(),
//                ]
//            ]
//        ]);
//
//    }
