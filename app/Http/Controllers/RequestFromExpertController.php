<?php

namespace App\Http\Controllers;


use App\Models\EconomicEvaluation;
use App\Models\Property_for_sale;
use App\Models\PropertyForInvestment;
use App\Models\Request_from_admin;
use App\Models\request_from_expert;
use App\Models\request_from_lawyer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class RequestFromExpertController extends Controller
{
    public function createRequestFromExpert(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $validator = Validator::make($request->all(),[
            'request_from_lawyer_id'=>'required|exists:request_from_lawyers,id',
            'economic_evaluation' => 'required|array',
            'economic_evaluation.number_of_chances' => 'required|integer',
            'economic_evaluation.negotiation_mode' => 'required',
            'economic_evaluation.expected_price' => 'required',
            'economic_evaluation.profit_percent' => 'required',
            'economic_evaluation.total_expected_taxes' => 'required',
            'economic_evaluation.buying_price' => 'required',
            'economic_evaluation.renting_price' => 'required',
            'economic_evaluation.chance_price' => 'required',
            'economic_evaluation.investment_time' => 'required|date',
            'economic_evaluation.incoming_time' => 'required|date',
            'economic_evaluation.investment_mode' => 'required|string',
            'economic_evaluation.property_management' => 'required|string',
            'note_admin' => 'string',
            'economic_evaluation.property_for_sale_id' => 'required|exists:property_for_sales,id',
            'economic_evaluation.agreed_negotiations_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $economicEvaluation = new EconomicEvaluation();
        $economicEvaluation->fill($request->economic_evaluation);
        $economicEvaluation->save();

        $requestForLawyer = request_from_lawyer::find($request->request_from_lawyer_id);
        if ($requestForLawyer) {
            $requestForLawyer->status = 'مقبول';
            $requestForLawyer->accept_user = 'مقبول';
            $requestForLawyer->save();
        }
        $propertyForSale = Property_for_sale::find($request->economic_evaluation['property_for_sale_id']);
        if ($propertyForSale) {
            $propertyForSale->expert_check = true;
            $propertyForSale->save();
        }

        $requestFromExpert = new request_from_expert();
        $requestFromExpert->economic_evaluation_id = $economicEvaluation->id;
        $requestFromExpert->request_from_lawyer_id = $request->request_from_lawyer_id;
        $requestFromExpert->note_admin = $request->note_admin;
        $requestFromExpert->status ='معلق' ;
        $requestFromExpert->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $requestFromExpert,
        ], 201);
    }

    public function getRejectedRequests()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $rejectedRequests = request_from_expert::where('status', 'مرفوض')->get();

        $responseData = $rejectedRequests->map(function ($request) {
            return [
                'id' => $request->id,
                'economic_evaluation_id' => $request->economic_evaluation_id,
                'note_admin' => $request->note_admin,
                'status' => $request->status,
            ];
        });

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

    public function updateEconomicEvaluation(Request $request, $id)
    {
        $requestFromlawyer = request_from_lawyer::find($id);

        if (!$requestFromlawyer) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $requestFromExpert = $requestFromlawyer->Request_from_expert;

        if (!$requestFromExpert) {
            return response()->json([
                'message' => __('messages.expert_request_not_found'),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'economic_evaluation' => 'required|array',
            'economic_evaluation.number_of_chances' => 'sometimes|integer',
            'economic_evaluation.negotiation_mode' => 'required',
            'economic_evaluation.expected_price' => 'sometimes|numeric',
            'economic_evaluation.profit_percent' => 'sometimes|numeric',
            'economic_evaluation.total_expected_taxes' => 'sometimes',
            'economic_evaluation.buying_price' => 'sometimes',
            'economic_evaluation.renting_price' => 'required',
            'economic_evaluation.chance_price' => 'sometimes',
            'economic_evaluation.investment_time' => 'sometimes|date',
            'economic_evaluation.incoming_time' => 'sometimes|date',
            'economic_evaluation.investment_mode' => 'sometimes|string',
            'economic_evaluation.property_management' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $economicEvaluation = $requestFromExpert->economic_evaluation;

        if ($economicEvaluation) {
            $economicEvaluation->fill($request->economic_evaluation);
            $economicEvaluation->save();
        }
        $requestFromExpert->status = 'معلق';
        $requestFromExpert->save();

        $requestFromlawyer->accept_admin='معلق';
        $requestFromlawyer->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $economicEvaluation,
        ], 200);
    }

    public function deleteRequest($id)
    {

        $requestFromExpert = request_from_expert::find($id);

        if (!$requestFromExpert) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $requestFromExpert->delete();

        return response()->json([
            'message' => __('messages.operation_success'),
        ], 200);
    }

    /* من اجل الاددمن*/
    public function getRequestFromExpertById($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $request = request_from_expert::with(['economic_evaluation.property', 'economic_evaluation.agreed_negotiation'])->find($id);

        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $property = $request->economic_evaluation->property;

        $images = $property->Property_image;
        $documents = $property->Property_document;
        $idImages = $property->id_image;

        $responseData = [
            'id' => $request->id,
            'economic_evaluation_id' => $request->economic_evaluation_id,
            'note_admin' => $request->note_admin,
            'status' => $request->status,
            'created_at' => $request->created_at->format('Y-m-d'),
            'economic_evaluation' => [
                'number_of_chances' => $request->economic_evaluation->number_of_chances,
                'expected_price' => $request->economic_evaluation->expected_price,
                'negotiation_mode' => $request->economic_evaluation->negotiation_mode,
                'profit_percent' => $request->economic_evaluation->profit_percent,
                'total_expected_taxes' => $request->economic_evaluation->total_expected_taxes,
                'buying_price' => $request->economic_evaluation->buying_price,
                'renting_price' => $request->economic_evaluation->renting_price,
                'chance_price' => $request->economic_evaluation->chance_price,
                'investment_time' => $request->economic_evaluation->investment_time,
                'incoming_time' => $request->economic_evaluation->incoming_time,
                'investment_mode' => $request->economic_evaluation->investment_mode,
                'property_management' => $request->economic_evaluation->property_management,
                'property' => [  // معلومات العقار
                    'id' => $property->id,
                    'user_id' => $property->user_id,
                    'property_type' => $property->property_type,
                    'area' => $property->area,
                    'number_of_rooms' => $property->number_of_rooms,
                    'number_of_bathrooms' => $property->number_of_bathrooms,
                    'property_age' => $property->property_age,
                    'decoration' => $property->decoration,
                    'kitchen_type' => $property->kitchen_type,
                    'flooring_type' => $property->flooring_type,
                    'overlook_from' => $property->overlook_from,
                    'balcony_size' => $property->balcony_size,
                    'painting_type' => $property->painting_type,
                    'price' => $property->price,
                    'pay_way' => $property->pay_way,
                    'state' => $property->state,
                    'exact_position' => $property->exact_position,
                    'contract' => $property->contract,
                    'legal_check' => $property->legal_check,
                    'expert_check' => $property->expert_check,
                    'accept' => $property->accept,
                    'images' => $images,
                    'documents' => $documents,
                    'id_images' => $idImages,
                ],
                'agreed_negotiation' => $request->economic_evaluation->agreed_negotiation,
            ],
        ];

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

    public function getPropertyByRequestId($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $request = request_from_lawyer::with([
            'property_for_sale.Property_image',
            'property_for_sale.Property_document',
            'property_for_sale.id_image',
            'Request_from_expert.economic_evaluation.property.Property_image',
            'Request_from_expert.economic_evaluation.property.Property_document',
            'Request_from_expert.economic_evaluation.property.id_image',
            'Request_from_expert.economic_evaluation.agreed_negotiation',
            'Request_from_expert.economic_evaluation.indicatorValues.indicator', // Include indicators
        ])->find($id);

        if (!$request) {
            return response()->json([
                'message' => ('messages.not_found'),
            ], 404);
        }

        if (!$request->property_for_sale) {
            return response()->json([
                'message' => ('messages.property_not_found'),
            ], 404);
        }

        $property = $request->property_for_sale;

        $propertyData = $property->toArray();
        $propertyData['images'] = $property->Property_image;
        $propertyData['documents'] = $property->Property_document;
        $propertyData['id_images'] = $property->id_image;

        $expertRequest = $request->Request_from_expert;
        $evaluation = $expertRequest?->economic_evaluation;
        $agreedNegotiation = $evaluation?->agreed_negotiation;

        // Handle indicator values if available
        $indicatorValues = $evaluation?->indicatorValues?->map(function ($item) {
                return [
                    'id' => $item->id,
                    'property_id' => $item->property_id,
                    'economic_evaluation_id' => $item->economic_evaluation_id,
                    'indicator_id' => $item->indicator_id,
                    'value' => $item->value,
                    // Optionally include indicator name/label
                    'indicator_name' => $item->indicator?->name,
                ];
            }) ?? [];

        $economicData = [
            'note_admin' => $expertRequest?->note_admin,
            'economic_evaluation' => $evaluation ? [
                'number_of_chances' => $evaluation->number_of_chances,
                'expected_price' => $evaluation->expected_price,
                'negotiation_mode' => $evaluation->negotiation_mode,
                'profit_percent' => $evaluation->profit_percent,
                'total_expected_taxes' => $evaluation->total_expected_taxes,
                'buying_price' => $evaluation->buying_price,
                'renting_price' => $evaluation->renting_price,
                'chance_price' => $evaluation->chance_price,
                'investment_time' => $evaluation->investment_time,
                'incoming_time' => $evaluation->incoming_time,
                'investment_mode' => $evaluation->investment_mode,
                'property_management' => $evaluation->property_management,
                'agreed_negotiation' => $agreedNegotiation ?: (object)[
                    'id' => null,
                    'Text_of_the_agreement' => null,
                    'created_at' => null,
                ],
                'indicator_values' => $indicatorValues,
            ] : [
                'number_of_chances' => null,
                'expected_price' => null,
                'profit_percent' => null,
                'total_expected_taxes' => null,
                'buying_price' => null,
                'renting_price' => null,
                'chance_price' => null,
                'investment_time' => null,
                'incoming_time' => null,
                'investment_mode' => null,
                'property_management' => null,
                'agreed_negotiation' => (object)[
                    'id' => null,
                    'Text_of_the_agreement' => null,
                    'created_at' => null,
                ],
                'indicator_values' => [],
            ]
        ];

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => array_merge(
                $propertyData,
                $economicData
            )
        ], 200);
    }

    public function getAllRequestsForAdmin()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $requests = request_from_expert::with([
            'economic_evaluation',
            'Request_from_lower'
        ]) ->where('status', '!=', 'مقبول')
        ->get();
        
        $responseData = $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'economic_evaluation_id' => $request->economic_evaluation_id,
                'note_admin' => $request->note_admin,
                'status' => $request->status,
                'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                'lawyer_acceptance_date' => $request->Request_from_lower ? $request->Request_from_lower->created_at->format('Y-m-d H:i:s') : null,
                'expert_acceptance_date' => $request->economic_evaluation ? $request->economic_evaluation->created_at->format('Y-m-d H:i:s') : null
            ];
        });

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

//    public function acceptRequest($id)
//    {
//        $userRole = auth()->user()->role_id;
//        if ($userRole !== 1) {
//            return response()->json([
//                'message' => trans('messages.unauthorized'),
//            ], 403);
//        }
//
//        $request = request_from_expert::find($id);
//
//        if (!$request) {
//            return response()->json([
//                'message' => __('messages.not_found'),
//            ], 404);
//        }
//
//        $request->status = 'مقبول';
//        $request->save();
//
//        if (!$request->economic_evaluation) {
//            return response()->json([
//                'message' => __('messages.not_found'),
//            ], 404);
//        }
//
//        $property = request_from_lawyer::find($request->request_from_lawyer_id);
//        if ($property) {
//            $property->accept_admin = 'مقبول';
//            $property->save();
//        } else {
//            return response()->json([
//                'message' => __('messages.not_found'),
//            ], 404);
//        }
//
//        $property = Property_for_sale::find($request->economic_evaluation->property_for_sale_id);
//        if ($property) {
//            $property->accept = true;
//            $property->save();
//        } else {
//            return response()->json([
//                'message' => __('messages.property_not_found'),
//            ], 404);
//        }
//
//
//        if($property->accept) {
//            PropertyForInvestment::create([
//                'property_id' => $request->economic_evaluation->property_for_sale_id,
//                'number_of_chances' => $request->economic_evaluation->number_of_chances,
//                'expected_price' => $request->economic_evaluation->expected_price,
//                'profit_percent' => $request->economic_evaluation->profit_percent,
//                'chance_price' => $request->economic_evaluation->chance_price,
//                'investment_time' => $request->economic_evaluation->investment_time,
//                'incoming_time' => $request->economic_evaluation->incoming_time,
//                'investment_mode' => $request->economic_evaluation->investment_mode,
//                'property_management' => $request->economic_evaluation->property_management,
//                'progress_percent' => 0,
//                'is_completed' => false,
//            ]);
//        }
//
//        $newRequest = new Request_from_admin();
//        $newRequest->request_from_expert_id = $request->id;
//        $newRequest->property_for_sale_id = $property->id;
//        $newRequest->type_request = 'buy request';
//        $newRequest->status = 'Stuck';
//        $newRequest->save();
//
//        return response()->json([
//            'message' => __('messages.operation_success'),
//            'data' => $request,
//        ], 200);
//    }


    public function acceptRequest($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $request = request_from_expert::find($id);

        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $request->status = 'مقبول';
        $request->save();

        if (!$request->economic_evaluation) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $property = request_from_lawyer::find($request->request_from_lawyer_id);
        if ($property) {
            $property->accept_admin = 'مقبول';
            $property->save();
        } else {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }


        $newRequest = new Request_from_admin();
        $newRequest->request_from_expert_id = $request->id;
        $newRequest->property_for_sale_id = $property->id;
        $newRequest->type_request = 'buy request';
        $newRequest->status = 'Stuck';
        $newRequest->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $request,
        ], 200);
    }


    public function processPropertyInvestment($propertyForSaleId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }


        $property = Property_for_sale::find($propertyForSaleId);
        if (!$property) {
            return response()->json([
                'message' => __('messages.property_not_found'),
            ], 404);
        }

        $property->accept = true;
        $property->save();

        if ($property->accept) {
            $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $propertyForSaleId)->first();
            if (!$economicEvaluation) {
                return response()->json([
                    'message' => __('messages.not_found'),
                ], 404);
            }

            PropertyForInvestment::create([
                'property_id' => $propertyForSaleId,
                'number_of_chances' => $economicEvaluation->number_of_chances,
                'expected_price' => $economicEvaluation->expected_price,
                'profit_percent' => $economicEvaluation->profit_percent,
                'chance_price' => $economicEvaluation->chance_price,
                'investment_time' => $economicEvaluation->investment_time,
                'incoming_time' => $economicEvaluation->incoming_time,
                'investment_mode' => $economicEvaluation->investment_mode,
                'property_management' => $economicEvaluation->property_management,
                'progress_percent' => 0,
                'is_completed' => false,
            ]);
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $property,
        ], 200);
    }


    public function rejectRequest(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $requestFromExpert = request_from_expert::find($id);

        if (!$requestFromExpert) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'note_admin' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $property = request_from_lawyer::find($requestFromExpert->request_from_lawyer_id);
        if ($property) {
            $property->accept_admin = 'مرفوض';
            $property->save();
        } else {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $requestFromExpert->status = 'مرفوض';
        $requestFromExpert->note_admin = $request->note_admin;
        $requestFromExpert->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $requestFromExpert,
        ], 200);
    }

}

