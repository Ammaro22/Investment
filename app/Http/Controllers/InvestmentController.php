<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use App\Models\EconomicEvaluation;
use App\Models\Investment;
use App\Models\PropertyForInvestment;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InvestmentController extends Controller
{


    public function approve_property($evaluation_id)
    {
        $user = auth()->user();

        if ($user->role_id != 1) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }


        $evaluation = EconomicEvaluation::with('property')->find($evaluation_id);


        if (!$evaluation) {
            return response()->json(['message' => trans('messages.not_found')]);
        }


        $property = $evaluation->property;


        if ($property->legal_check && $property->expert_check) {
            $property->accept = true;
            $property->save();

            $evaluation->status = 'approved';
            $evaluation->save();
        }


        $is_exists = PropertyForInvestment::where('property_id', $property->id)->first();


        if ($is_exists) {
            return response()->json(['message' => trans('messages.already_approved')]);
        }


        if ($property->accept) {
            PropertyForInvestment::create([
                'property_id' => $evaluation->property_id,
                'number_of_chances' => $evaluation->number_of_chances,
                'expected_price' => $evaluation->expected_price,
                'profit_percent' => $evaluation->profit_percent,
                'chance_price' => $evaluation->chance_price,
                'investment_time' => $evaluation->investment_time,
                'incoming_time' => $evaluation->incoming_time,
                'investment_mode' => $evaluation->investment_mode,
                'property_management' => 'investment',
                'progress_percent' => 0,
                'is_completed' => false,
            ]);

            return response()->json(['message' => trans('messages.operation_success')]);
        }
        return response()->json(['message' => trans('messages.operation_failed')]);
    }


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


        $property = PropertyForInvestment::with('property')->whereHas('property', function ($query) use ($property_type) {
            $query->where('property_type', $property_type);

        })->get();


        if ($property->isEmpty()) {
            return response()->json(['message' => trans('messages.no_properties_found')]);
        }


        $properties = $property->map(function ($item) {

            return $this->re_arrange($item);

        });

        return response()->json([
            'message' => trans('messages.properties_found'),
            'data' => $properties
        ]);

    }


    public function ShowPropertyByInvestmentType(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'investment_mode' => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }


        $property = PropertyForInvestment::with('property')->where('investment_mode', $request->investment_mode)->get();

        if ($property->isEmpty()) {
            return response()->json(['message' => trans('messages.no_properties_found')]);
        }


        $properties = $property->map(function ($item) {
            return $this->re_arrange($item);

        });

        return response()->json([
            'message' => trans('messages.properties_found'),
            'data' => $properties
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


        if ($property->number_of_chances < $request->chance_invested) {
            return response()->json(['message' => trans('messages.no_chance_available')]);
        }


        $amount = $property->chance_price * $request->chance_invested;


        $investmentWallet = $user->wallets()->where('wallet_type', 'investment')->first();
        if (!$investmentWallet || $investmentWallet->balance < $amount) {
            return response()->json(['message' => trans('messages.insufficient_balance')], 422);
        }


        $property->number_of_chances -= $request->chance_invested;

        $property->save();

        $walletController = new WalletController();

        $walletController->transferToPlatform(new Request(['amount' => $amount]));


        Investment::create([

            'user_id' => $user->id,
            'property_for_investment_id' => $property->id,
            'chance_invested' => $request->chance_invested,
            'amount_payed' => $amount,
        ]);

        $total_chances = $property->number_of_chances;

        $invested_chances = $property->investment->sum('chance_invested');

        $progress = $total_chances > 0 ? round(($invested_chances / $total_chances) * 100, 2) : 0;

        $property->update(['progress_percent' => $progress]);

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

        $property_invested = Investment::with('property_invested')->where('user_id', $user->id)->get();


        if ($property_invested->isEmpty()) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $investments = $property_invested->map(function ($item) {
            $main = collect($item->toArray())->except('property_invested');

            $related = collect($item->property_invested)->except('property_management', 'property_id');

            $re_arrange = $main->merge($related);

            $created_at = Carbon::parse($re_arrange->pull('created_at'))->format('Y-m-d');
            $updated_at = Carbon::parse($re_arrange->pull('updated_at'))->format('Y-m-d');

            return $re_arrange->put('created_at', $created_at)->put('updated_at', $updated_at);


        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $investments
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

        $investments = Investment::where('user_id', $user->id)->get();


        if ($investments->isEmpty()) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        $listOfInvestment = $investments->map(function ($investment) {
            return $this->Format_timeStamp_Map($investment);
        });


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $listOfInvestment
        ]);

    }





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

}





