<?php

namespace App\Http\Controllers\v1;

use App\Models\Property_for_sale;
use App\Models\request_from_lawyer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RequestFromLawyerController extends BaseController
{

    public function getRequestWithUser()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $requests = request_from_lawyer::with(['property_for_sale.user', 'Request_from_expert.economic_evaluation.agreed_negotiation'])->get();

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $responseData = $requests->map(function ($request) {
            $property = $request->property_for_sale;
            $propertyInfo = $property ? $property->state . ' ' . $property->exact_position : null;

            $expertRequest = $request->Request_from_expert;
            $agreedNegotiationStatus = $expertRequest && $expertRequest->economic_evaluation->agreed_negotiation ? $expertRequest->economic_evaluation->agreed_negotiation->status : null;

            return [
                'user_id' => $property->user_id,
                'request_from_lawyer_id' => $request->id,
                'property_for_sale_id' => $request->property_for_sale_id,
                'status_request' => $request->status,
                'accept_admin' => $request->accept_admin,
                'by_whom' => $request->by_whom,
                'user_name' => $property->user->name,
                'created_at' => $request->created_at->format('Y-m-d'),
                'agreed_negotiation_status' => $agreedNegotiationStatus,
                'property_info' => $propertyInfo,
            ];
        });

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

    public function getRequestforUser($userId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $requests = request_from_lawyer::with([
            'property_for_sale.user',
            'Request_from_expert.economic_evaluation.agreed_negotiation'
        ])
            ->whereHas('property_for_sale', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => ('messages.not_found'),
            ], 404);
        }

        $responseData = $requests->map(function ($request) {
            $property = $request->property_for_sale;
            $propertyInfo = $property ? $property->state . ' ' . $property->exact_position : null;

            $expertRequest = $request->Request_from_expert;
            $agreedNegotiation = $expertRequest?->economic_evaluation?->agreed_negotiation;

            return [
                'user_id' => $property->user_id,
                'user_name' => $property->user->name,
                'property_info' => $propertyInfo,
                'requestId' => $request->id,
                'property_for_sale_id' => $request->property_for_sale_id,
                'status_request' => $request->status,
                'accept_admin' => $request->accept_admin,
                'created_at' => $request->created_at->format('Y-m-d'),
                'agreed_negotiation' => $agreedNegotiation ? [
                    'id' => $agreedNegotiation->id,
                    'status' => $agreedNegotiation->status,
                    'text_of_the_agreement' => $agreedNegotiation->Text_of_the_agreement,
                    'created_at' => $agreedNegotiation->created_at->format('Y-m-d H:i:s'),
                ] : null,
            ];
        });

        return response()->json([
            'message' => ('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

//    public function getPropertyByRequestId($id)
//    {
//        $userRole = auth()->user()->role_id;
//        if ($userRole !== 3) {
//            return response()->json([
//                'message' => trans('messages.unauthorized'),
//            ], 403);
//        }
//
//        $request = request_from_lawyer::with([
//            'property_for_sale.Property_image',
//            'property_for_sale.Property_document',
//            'property_for_sale.id_image',
//            'Request_from_expert.economic_evaluation.property.Property_image',
//            'Request_from_expert.economic_evaluation.property.Property_document',
//            'Request_from_expert.economic_evaluation.property.id_image',
//            'Request_from_expert.economic_evaluation.agreed_negotiation'
//        ])->find($id);
//
//        if (!$request) {
//            return response()->json([
//                'message' => ('messages.not_found'),
//            ], 404);
//        }
//
//        if (!$request->property_for_sale) {
//            return response()->json([
//                'message' => ('messages.property_not_found'),
//            ], 404);
//        }
//
//        $property = $request->property_for_sale;
//
//
//        $propertyData = $property->toArray();
//        $propertyData['images'] = $property->Property_image;
//        $propertyData['documents'] = $property->Property_document;
//        $propertyData['id_images'] = $property->id_image;
//
//
//        $expertRequest = $request->Request_from_expert;
//        $evaluation = $expertRequest?->economic_evaluation;
//        $agreedNegotiation = $evaluation?->agreed_negotiation;
//
//
//        $economicData = [
//            'note_admin' => $expertRequest?->note_admin,
//            'economic_evaluation' => $evaluation ? [
//                'number_of_chances' => $evaluation->number_of_chances,
//                'expected_price' => $evaluation->expected_price,
//                'profit_percent' => $evaluation->profit_percent,
//                'total_expected_taxes' => $evaluation->total_expected_taxes,
//                'buying_price' => $evaluation->buying_price,
//                'renting_price' => $evaluation->renting_price,
//                'chance_price' => $evaluation->chance_price,
//                'investment_time' => $evaluation->investment_time,
//                'incoming_time' => $evaluation->incoming_time,
//                'investment_mode' => $evaluation->investment_mode,
//                'property_management' => $evaluation->property_management,
//                'agreed_negotiation' => $agreedNegotiation ?: (object)[
//                    'id' => null,
//                    'Text_of_the_agreement' => null,
//                    'created_at' => null,
//                ],
//            ] : [
//                'number_of_chances' => null,
//                'expected_price' => null,
//                'profit_percent' => null,
//                'total_expected_taxes' => null,
//                'buying_price' => null,
//                'renting_price' => null,
//                'chance_price' => null,
//                'investment_time' => null,
//                'incoming_time' => null,
//                'investment_mode' => null,
//                'property_management' => null,
//                'agreed_negotiation' => (object)[
//                    'id' => null,
//                    'Text_of_the_agreement' => null,
//                    'created_at' => null,
//                ],
//            ]
//        ];
//
//
//
//        return response()->json([
//            'message' => __('messages.operation_success'),
//            'data' => array_merge(
//                $propertyData,
//                $economicData
//            )
//        ], 200);
//    }

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
            'Request_from_expert.economic_evaluation.agreed_negotiation'
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


        $economicData = [
            'note_admin' => $expertRequest?->note_admin,
            'economic_evaluation' => $evaluation ? [
                'number_of_chances' => $evaluation->number_of_chances,
                'expected_price' => $evaluation->expected_price,
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


    public function deleteRequest($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $request = request_from_lawyer::find($id);

        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $request->delete();

        return response()->json([
            'message' => trans('messages.delete_success'),
        ], 200);
    }


}

