<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorValue;
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


    public function storeValueToIndicator(Request $request)
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $validator=Validator::make($request->all(),[
            'economic_evaluation_id'=>'required|exists:economic_evaluations,id',
            'indicator_id'=>'required|exists:indicators,id',
            'value'=>'required|numeric'
        ]);

        if($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()],400);
        }

        $indicator_values=IndicatorValue::create([
            'economic_evaluation_id'=>$request->economic_evaluation_id,
            'indicator_id'=>$request->indicator_id,
            'value'=>$request->value
        ]);

        return response()->json(['message'=>trans('messages.operation_success')]);
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



    public function updateValueOfIndicator(Request $request,$indicatorValue_id)
    {
        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=3)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $validator=Validator::make($request->all(),[
            'value'=>'required|numeric'
        ]);

        if($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()],400);
        }

        $indicatorValue=IndicatorValue::find($indicatorValue_id);
        if(!$indicatorValue)
        {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicatorValue->update($request->only([
            'value'
        ]));
        return response()->json(['message'=>trans('messages.operation_success'),'data'=>$indicatorValue]);
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

        $Values=$indicatorValue->map(function ($value)
        {
            $info = $value->values->first();

            return [
                'indicator_id' => $value->id,
                'economic_evaluation_id' => optional($info)->economic_evaluation_id,
                'name' => $value->name,
                'recommended_min' => $value->recommended_min,
                'recommended_max' => $value->recommended_max,
                'ValueAssigned' => optional($info)->value,
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
