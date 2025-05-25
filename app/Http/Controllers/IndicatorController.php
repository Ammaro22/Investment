<?php

namespace App\Http\Controllers;

use App\Models\EconomicEvaluation;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Property_for_sale;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class IndicatorController extends Controller
{

    public function storeIndicator(Request $request)
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $validator=Validator::make($request->all(),[
            'name'=>'required|string',
            'recommended_min'=>'required|numeric',
            'recommended_max'=>'required|numeric'
        ]);

        if($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()],400);
        }

        $indicator=Indicator::create([
            'name'=>$request->name,
            'recommended_min'=>$request->recommended_min,
            'recommended_max'=>$request->recommended_max
        ]);

        return response()->json(['message'=>trans('messages.operation_success')]);
    }





    public function storeValueToIndicator(Request $request, $property_id)
    {
        $user = auth()->user();
        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        $validator = Validator::make($request->all(), [
            'indicators' => 'required|array|min:1',
            'indicators.*.indicator_id' => 'required|exists:indicators,id',
            'indicators.*.value' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $property = Property_for_sale::find($property_id);
        if (!$property) {
            return response()->json(['message' => 'Property not found.'], 404);
        }

        $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $property_id)->first();
        if (!$economicEvaluation) {
            return response()->json(['message' => 'Economic evaluation not found.'], 404);
        }

        foreach ($request->indicators as $indicatorData) {
            $exists = IndicatorValue::where('economic_evaluation_id', $economicEvaluation->id)
                ->where('property_id', $property_id)
                ->where('indicator_id', $indicatorData['indicator_id'])
                ->exists();


            if (!$exists) {
                IndicatorValue::create([
                    'economic_evaluation_id' => $economicEvaluation->id,
                    'property_id' => $property_id,
                    'indicator_id' => $indicatorData['indicator_id'],
                    'value' => $indicatorData['value'],
                ]);
            }
            else{
                return response()->json(['message'=>'value of indicator already assigned']);
            }
        }

        return response()->json(['message' => trans('messages.operation_success')]);
    }






    public function deleteIndicator($indicator_id)
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $indicator=Indicator::find($indicator_id);
        if(!$indicator)
        {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicator->delete();
        return response()->json(['message'=>trans('messages.operation_success')]);
    }

    public function updateIndicator(Request $request,$indicator_id)
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $validator=Validator::make($request->all(),[
            'name'=>'required|string',
            'recommended_min'=>'required|numeric',
            'recommended_max'=>'required|numeric'
        ]);

        if($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()],400);
        }

        $indicator=Indicator::find($indicator_id);
        if(!$indicator)
        {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicator->update($request->only([
            'name',
            'recommended_min',
            'recommended_max'
        ]));
        return response()->json(['message'=>trans('messages.operation_success'),'data'=>$indicator]);
    }


    public function updateValuesOfIndicator(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        $validator = Validator::make($request->all(), [
            'indicators' => 'required|array',
            'indicators.*.id' => 'required|exists:indicator_values,id',
            'indicators.*.value' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $updated = [];

        foreach ($request->indicators as $item) {
            $indicatorValue = IndicatorValue::find($item['id']);

            if ($indicatorValue) {
                $indicatorValue->update([
                    'value' => $item['value'],
                ]);
                $updated[] = $indicatorValue;
            }
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $updated,
        ], 200);
    }



    public function deleteValueOfIndicator($indicatorValue_id)
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $indicatorValue=IndicatorValue::find($indicatorValue_id);
        if(!$indicatorValue)
        {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicatorValue->delete();
        return response()->json(['message'=>trans('messages.operation_success')]);
    }


    public function getIndicators()
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $indicator = Indicator::all();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $indicator,
        ], 200);
    }


    public function getIndicatorWithValues()
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $indicatorValue = Indicator::with('values')->get();

        if($indicatorValue->isEmpty())
        {
            return response()->json(['message' => trans('messages.not_found')]);

        }

        $Values = $indicatorValue->map(function ($indicator) {
            return [
                'indicator_id' => $indicator->id,
                'name' => $indicator->name,
                'recommended_min' => $indicator->recommended_min,
                'recommended_max' => $indicator->recommended_max,
                'values' => $indicator->values->map(function ($val) {
                    return [
                        'economic_evaluation_id' => $val->economic_evaluation_id,
                        'ValueAssigned' => $val->value,
                    ];
                }),
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $Values,
        ], 200);
    }


    public function getValuesOfIndicator()
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $indicatorValue = IndicatorValue::all();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $indicatorValue,
        ], 200);
    }
}



/* public function storeValueToIndicator(Request $request, $property_id)
 {
     $user = auth()->user();
     if (!$user || $user->role_id != 3) {
         return response()->json(['message' => trans('messages.unauthorized')], 403);
     }

     $validator = Validator::make($request->all(), [
         'economic_evaluation_id' => 'required|exists:economic_evaluations,id',
         'indicators' => 'required|array|min:1',
         'indicators.*.indicator_id' => 'required|exists:indicators,id',
         'indicators.*.value' => 'required|numeric',
     ]);

     if ($validator->fails()) {
         return response()->json(['errors' => $validator->errors()], 400);
     }

     if (!Property_for_sale::find($property_id)) {
         return response()->json(['message' => 'Property not found.'], 404);
     }

     $economicEvaluation = EconomicEvaluation::find($request->economic_evaluation_id);
     if (!$economicEvaluation) {
         return response()->json(['message' => 'Economic evaluation not found.'], 404);
     }

     if ($economicEvaluation->property_for_sale_id != $property_id) {
         return response()->json(['message' => 'This economic evaluation does not belong to the given property.'], 400);
     }

     foreach ($request->indicators as $indicatorData) {
         IndicatorValue::create([
             'economic_evaluation_id' => $request->economic_evaluation_id,
             'property_id' => $property_id,
             'indicator_id' => $indicatorData['indicator_id'],
             'value' => $indicatorData['value'],
         ]);
     }

     return response()->json(['message' => trans('messages.operation_success')]);
 }
*/
