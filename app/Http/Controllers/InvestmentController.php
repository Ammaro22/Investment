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
                'total_chance' => $evaluation->total_chance,
                'expected_price' => $evaluation->expected_price,
                'return_rate' => $evaluation->expected_return,
                'chance_price'=>$evaluation->chance_price,
                'deadline_investment'=>$evaluation->deadline_investment,
                'investment_type'=>$evaluation->investment_type,
                'progress_percent' => 0,
                'is_completed' => false,
            ]);

            return response()->json(['message' => trans('messages.operation_success')]);
        }
        return response()->json(['message'=>trans('messages.operation_failed')]);
    }


























}
