<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use App\Models\EconomicEvaluation;
use App\Models\PropertyForInvestment;
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
        $evaluation = EconomicEvaluation::with('property')->findOrFail($evaluation_id);

        $evaluation->status = 'approved';
        $evaluation->save();


        $property = $evaluation->property;
        $property->accept = true;
        $property->save();

        $is_exists = PropertyForInvestment::where('property_id', $property->id)->first();

        if($is_exists)
        {
            return response()->json(['message'=>trans('messages.operation_failed')]);
        }

        if (($property->legal_check && $property->expert_check && $property->accept)) {
            PropertyForInvestment::create([
                'property_id' => $evaluation->property_id,
                'number_of_chances' => $evaluation->number_of_chances,
                'expected_price' => $evaluation->expected_price,
                'profit_percent' => $evaluation->profit_percent,
                'chance_price'=>$evaluation->chance_price,
                'investment_time'=>$evaluation->investment_time,
                'incoming_time'=>$evaluation->incoming_time,
                'investment_mode'=>$evaluation->investment_mode,
                'property_management'=>'investment',
                'progress_percent' => 0,
                'is_completed' => false,
            ]);

            return response()->json(['message' => trans('messages.operation_success')]);
        }
        return response()->json(['message'=>trans('messages.operation_failed')]);
    }


























}
