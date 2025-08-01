<?php

namespace App\Http\Controllers\v1;

use App\Models\EconomicEvaluation;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Property_for_sale;
use App\Services\PropertyAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class IndicatorController extends BaseController
{

    public function storeIndicator(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'recommended_min' => 'required|numeric',
            'recommended_max' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $indicator = Indicator::create([
            'name' => $request->name,
            'recommended_min' => $request->recommended_min,
            'recommended_max' => $request->recommended_max
        ]);

        $this->firebaseNotification->sendToUser($user,'store_economic_indicator');

        return response()->json(['message' => trans('messages.operation_success')]);
    }


//    public function storeValueToIndicator(Request $request)
//    {
//        $user = auth()->user();
//        if (!$user || $user->role_id != 3) {
//            return response()->json(['message' => trans('messages.unauthorized')], 403);
//        }
//
//        $validator = Validator::make($request->all(), [
//            'property_id' => 'required|exists:property_for_sales,id',
//            'indicator_id' => 'required|exists:indicators,id',
//            'data' => 'required|array',
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['errors' => $validator->errors()], 400);
//        }
//
//        $property_id = $request->property_id;
//        $indicator_id = $request->indicator_id;
//        $data = $request->data; // البيانات لحساب القيمة
//
//        $property = Property_for_sale::find($property_id);
//        if (!$property) {
//            return response()->json(['message' => __('messages.not_found'),], 404);
//        }
//
//        $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $property_id)->orderBy('created_at', 'desc')->first();
//        if (!$economicEvaluation) {
//            return response()->json(['message' => __('messages.not_found'),], 404);
//        }
//
//        // حساب القيمة بناءً على القوانين
//        $indicator = Indicator::find($indicator_id);
//        $valueResult = PropertyAnalysisService::calculateValue($indicator, $data);
//
//        if (isset($valueResult['error'])) {
//            return response()->json(['message' => $valueResult['error']], 400);
//        }
//
//        $value = $valueResult;
//
//        IndicatorValue::create([
//            'economic_evaluation_id' => $economicEvaluation->id,
//            'property_id' => $property_id,
//            'indicator_id' => $indicator_id,
//            'value' => $value,
//        ]);
//        return response()->json(['message' => trans('messages.operation_success')]);
//    }

    public function storeValuesToIndicators(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:property_for_sales,id',
            'indicators' => 'required|array|min:1',
            'indicators.*.indicator_id' => 'required|exists:indicators,id',
            'indicators.*.data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $property_id = $request->property_id;

        $property = Property_for_sale::find($property_id);
        if (!$property) {
            return response()->json(['message' => ('messages.not_found')], 404);
        }

        $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $property_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$economicEvaluation) {
            return response()->json(['message' => ('messages.not_found')], 404);
        }

        $results = [];

        foreach ($request->indicators as $indicatorInput) {
            $indicator = Indicator::find($indicatorInput['indicator_id']);
            $data = $indicatorInput['data'];

            $valueResult = PropertyAnalysisService::calculateValue($indicator, $data);

            if (isset($valueResult['error'])) {
                $results[] = [
                    'indicator_id' => $indicator->id,
                    'error' => $valueResult['error'],
                ];
                continue;
            }

            IndicatorValue::updateOrCreate(
                [
                    'economic_evaluation_id' => $economicEvaluation->id,
                    'property_id' => $property_id,
                    'indicator_id' => $indicator->id,
                ],
                ['value' => $valueResult]
            );

            $results[] = [
                'indicator_id' => $indicator->id,
                'value' => $valueResult,
                'status' => 'success',
            ];
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $results,
        ]);
    }


    public function deleteIndicator($indicator_id)
    {
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $indicator = Indicator::find($indicator_id);
        if (!$indicator) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicator->delete();
        $this->firebaseNotification->sendToUser($user,'delete_economic_indicator');

        return response()->json(['message' => trans('messages.operation_success')]);
    }

    public function updateIndicator(Request $request, $indicator_id)
    {
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'recommended_min' => 'required|numeric',
            'recommended_max' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $indicator = Indicator::find($indicator_id);
        if (!$indicator) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicator->update($request->only([
            'name',
            'recommended_min',
            'recommended_max'
        ]));
        $this->firebaseNotification->sendToUser($user,'update_economic_indicator');

        return response()->json(['message' => trans('messages.operation_success'), 'data' => $indicator]);
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
        $this->firebaseNotification->sendToUser($user,'update_economic_indicator');

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $updated,
        ], 200);
    }

    public function deleteValueOfIndicator($indicatorValue_id)
    {
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $indicatorValue = IndicatorValue::find($indicatorValue_id);
        if (!$indicatorValue) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $indicatorValue->delete();
        $this->firebaseNotification->sendToUser($user,'delete_economic_indicator');

        return response()->json(['message' => trans('messages.operation_success')]);
    }


    public function getIndicators()
    {
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $indicator = Indicator::all();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $indicator,
        ], 200);
    }


    public function getIndicatorWithValues()
    {
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $indicatorValue = Indicator::with('values')->get();

        if ($indicatorValue->isEmpty()) {
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
        $user = auth()->user();
        $userRole = $user->role_id;
        if (!$user || $userRole != 3) {
            return response()->json(['message' => trans('messages.unauthorized')]);
        }
        $indicatorValue = IndicatorValue::all();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $indicatorValue,
        ], 200);
    }


    public function getIndicatorValuesForProperty($property_id)
    {
        $user = auth()->user();
        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        // Get the most recent economic evaluation for the property
        $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $property_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$economicEvaluation) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }

        $indicatorValues = $economicEvaluation->indicatorValues()->with('indicator')->get();

        $result = $indicatorValues->map(function ($item) {
            return [
                'indicator_id' => $item->indicator_id,
                'indicator_name' => $item->indicator->name,
                'arabic_name' => $item->indicator->arabic_name,
                'value' => $item->value,
                'recommended_min' => $item->indicator->recommended_min,
                'recommended_max' => $item->indicator->recommended_max,
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'property_id' => $property_id,
            'economic_evaluation_id' => $economicEvaluation->id,
            'indicators' => $result,
        ], 200);
    }



}
