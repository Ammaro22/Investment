<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use App\Models\CompletedProperty;
use App\Models\EconomicEvaluation;
use App\Models\Investment;
use App\Models\Profit;
use App\Models\PropertyForInvestment;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InvestmentController extends Controller
{


    public function ShowProperty()
    {

        $property = PropertyForInvestment::with('property')->get();


        $properties = $property->map(function ($item) {

            return $this->re_arrange($item);
        });


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $properties
        ]);


    }

    public function ShowPropertyByType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_type' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $property_type = $request->property_type;

        $property = PropertyForInvestment::with('property')
            ->whereHas('property', function ($query) use ($property_type) {
                $query->where('property_type', $property_type);
            })
            ->paginate(5);

        if ($property->isEmpty()) {
            return response()->json(['message' => trans('messages.no_properties_found')]);
        }

        $properties = $property->map(function ($item) {
            $rearrangedItem = $this->re_arrange($item);
            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }
            $rearrangedItem = array_merge([
                'property_for_investment_id' => $item->id],
                 $rearrangedItem, [
                'property_images'=>$item->property->Property_image]);

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

        $validator = Validator::make($request->all(), [

            'investment_mode' => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }


        $property = PropertyForInvestment::with('property')->where('investment_mode', $request->investment_mode)->paginate(5);

        if ($property->isEmpty()) {
            return response()->json(['message' => trans('messages.no_properties_found')]);
        }


        $properties = $property->map(function ($item) {
            $rearrangedItem= $this->re_arrange($item);
            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }
            $rearrangedItem = array_merge([
                'property_for_investment_id' => $item->id],
                $rearrangedItem, [
                 'property_images'=>$item->property->Property_image]);

            return $rearrangedItem;

        });

        return response()->json([
            'message' => trans('messages.properties_found'),
            'data' =>[
                 'properties'=>$properties,
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

        $property = PropertyForInvestment::with('property')->find($PropertyId);

        if (!$property) {
            return response()->json(['message' => trans('messages.operation_failed')]);
        }


        $properties = $this->re_arrange($property);


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $properties
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

        if ($originalChances < $request->chance_invested) {
            return response()->json(['message' => trans('messages.no_chance_available')]);
        }

        $amount = $property->chance_price * $request->chance_invested;

        $investmentWallet = $user->wallets()->where('wallet_type', 'investment')->first();
        if (!$investmentWallet || $investmentWallet->balance < $amount) {
            return response()->json(['message' => trans('messages.insufficient_balance')], 422);
        }

        $walletController = new WalletController();
        $walletController->transferToPlatform(new Request(['amount' => $amount]));

        Investment::create([
            'user_id' => $user->id,
            'property_for_investment_id' => $property->id,
            'chance_invested' => $request->chance_invested,
            'amount_payed' => $amount,
        ]);

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


        if ($property_invested->isEmpty()) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $investments = $property_invested->map(function ($item) {
            $main = collect($item->toArray())->except('property_invested');

            $related = collect($item->property_invested)->except('property_management', 'property_id');
            $related2=collect($item->property_invested->property)->except('legal_check', 'expert_check', 'accept', 'user_id', 'price');
            $re_arrange = $main->merge($related)->merge($related2);

            $created_at = Carbon::parse($re_arrange->pull('created_at'))->format('Y-m-d');
            $updated_at = Carbon::parse($re_arrange->pull('updated_at'))->format('Y-m-d');

            $rearrangedItem= $re_arrange->put('created_at', $created_at)->put('updated_at', $updated_at);


            if ($rearrangedItem instanceof \Illuminate\Support\Collection) {
                $rearrangedItem = $rearrangedItem->toArray();
            }
            $rearrangedItem = array_merge(
                $rearrangedItem, [
                'property_images'=>$item->property_invested->property->Property_image]);

            return $rearrangedItem;

        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                 'properties'=>$investments,
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

        $investments = Investment::where('user_id', $user->id)->paginate(5);


        if ($investments->isEmpty()) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $listOfInvestment = $investments->map(function ($investment) {
            return $this->Format_timeStamp_Map($investment);
        });


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties'=> $listOfInvestment,
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

    /*للنسبة في portfolio*/

    public function ShowPercentageOfInvestments()
    {

        $user=auth()->user();

        $userRole=$user->role_id;

        if(!$user||$userRole!=2)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }

        $Wallet=Wallet::where('user_id',$user->id)->where('wallet_type','investment')->first();

        if(!$Wallet)
        {
            return response()->json(['message'=>trans('messages.not_found')]);
        }

        $Investment=Investment::where('user_id',$user->id)->get();

        $totalIn = Transaction::where('wallet_id', $Wallet->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $amount_payed=$Investment->sum('amount_payed');

        $percentage=$totalIn> 0 ?round(($amount_payed/$totalIn)*100):0;


        return response()->json([
            'message'=>trans('messages.operation_success'),
            'data'=>['percentage'=>$percentage]
        ]);
    }













  /* call within invest function*/

    public function CalculateNetProfit($property_id)
    {
        $property = PropertyForInvestment::with('investment')->find($property_id);

        if (!$property || !$property->is_completed)
        {
            return response()->json(['message' => trans('messages.operation_failed')]);
        }

        $economic = EconomicEvaluation::where('property_id', $property_id)->first();

        if (!$economic)
        {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $existingProfits = Profit::whereHas('completedProperty', function($q) use ($property_id) {
            $q->where('property_for_investment_id', $property_id);
        })->count();

        if ($existingProfits > 0)
        {
            return response()->json(['message' => trans('messages.profits_already_calculated')]);
        }

        $total_expected_taxes = $economic->total_expected_taxes;

        $profit_percent = $property->profit_percent;

        $renting = $economic->renting_price;

        $selling=$economic->baying_price;

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

            if($economic->property_management=='rent')
            {
                $profit_amount = $renting * $profit_ratio;
            }
            else
            {
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

        if (!$completedProperty)
        {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $existingProfit = Profit::where('completed_property_id' , $completedProperty->id)->where('user_id' , $user_id)->first();

        if ($existingProfit)
        {
            return response()->json(['message' => trans('messages.profit_already_exists')]);
        }

        $economic = EconomicEvaluation::where('property_id', $property_id)->first();

        if (!$economic->incoming_time)
        {
            return response()->json(['message' => trans('messages.not_found')]);
        }
        Profit::create([
            'completed_property_id' => $completedProperty->id,
            'user_id' => $user_id,
            'profit_amount' => $net_profit,
            'scheduled_date'=>$economic->incoming_time,
            'transfer_status'=>'pending',
            'transfer_attempts'=>0,
            'processed_at'=>now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);



        return response()->json(['message' => 'success']);
    }






    /* call within invest function*/
    public function isComplete($property_id)
    {
        if(!$property_id) {
            return false;
        }

        $investment = PropertyForInvestment::where('property_id', $property_id)->first();
        if(!$investment) {
            return false;
        }

        if($investment->number_of_chances == 0) {
            $investment->update(['is_completed' => true]);
            return true;
        }

        return false;
    }





    public function re_arrange($property)
    {

        $main = collect($property->toArray())->except('property', 'property_id', 'property_management');

        $related = collect($property->property)->except(['legal_check', 'expert_check', 'accept', 'user_id', 'price']);

        $re_arrange = $main->merge($related);

        $created_at = Carbon::parse($re_arrange->pull('created_at'))->format('Y-m-d');
        $updated_at = Carbon::parse($re_arrange->pull('updated_at'))->format('Y-m-d');

        return $re_arrange->put('created_at', $created_at)->put('updated_at', $updated_at);

    }



    public function Format_timeStamp_Map($investment)
    {

        $ArrayWallet = $investment->toArray();
        $ArrayWallet['created_at'] = Carbon::parse($investment->created_at)->format('Y-m-d');
        $ArrayWallet['updated_at'] = Carbon::parse($investment->updated_at)->format('Y-m-d');
        return $ArrayWallet;


    }











//
//    public function getCompletedProperty()
//    {
//
//        $user = auth()->user();
//
//        $userRole = $user->role_id;
//
//        if (!$user || $userRole != 3) {
//            return response()->json(['message' => trans('messages.unauthorized')]);
//        }
//
//        $properties = PropertyForInvestment::with('property')
//            ->where('is_completed', true)
//            ->get();
//
//
//        if ($properties->isEmpty()) {
//            return response()->json(['message' => trans('messages.no_properties_found')]);
//        }
//
//        foreach ($properties as $property){
//            $existing = CompletedProperty::where('property_for_investment_id', $property->id)->first();
//
//            if (!$existing) {
//
//                CompletedProperty::create([
//                    'property_for_investment_id' => $property->id,
//                    'property_management' => $property->property_management,
//                    'created_at' => now(),
//                    'updated_at' => now()
//                ]);
//
//
//            }
//        }
//        $formatted = $properties->map(function ($item) {
//            return $this->re_arrange($item);
//
//        });
//
//        return  response()->json([
//            'message'=>trans('messages.properties_found'),
//            'data'=>$formatted
//        ]);
//    }




}





