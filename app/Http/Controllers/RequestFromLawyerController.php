<?php

namespace App\Http\Controllers;

use App\Models\Property_for_sale;
use App\Models\request_from_lawyer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RequestFromLawyerController extends Controller
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
                'request_from_lawyer_id' => $request->id,
                'property_for_sale_id' => $request->property_for_sale_id,
                'status_request' => $request->status,
                'accept_admin' => $request->accept_admin,
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


    public function getPropertyByRequestId($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        // الحصول على طلب المحامي مع جميع العلاقات المطلوبة
        $request = request_from_lawyer::with([
            'property_for_sale.Property_image',
            'property_for_sale.Property_document',
            'property_for_sale.id_image',
            'Request_from_expert',
            'Request_from_expert.economic_evaluation',
            'Request_from_expert.economic_evaluation.agreed_negotiation'
        ])->find($id);

        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        // التحقق من وجود العقار
        if (!$request->property_for_sale) {
            return response()->json([
                'message' => __('messages.property_not_found'),
            ], 404);
        }

        // تهيئة البيانات الإضافية
        $additionalData = [
            'note_admin' => null,
            'text_of_the_agreement' => null
        ];

        // التحقق من وجود طلب الخبير والبيانات المرتبطة به
        if ($request->Request_from_expert) {
            $additionalData['note_admin'] = $request->Request_from_expert->note_admin;

            if ($request->Request_from_expert->economic_evaluation &&
                $request->Request_from_expert->economic_evaluation->agreed_negotiation) {
                $additionalData['text_of_the_agreement'] = $request->Request_from_expert->economic_evaluation->agreed_negotiation->Text_of_the_agreement;
                $additionalData['negotiation_id'] = $request->Request_from_expert->economic_evaluation->agreed_negotiation->id;
            }
        }

        // إعداد البيانات للإرجاع
        $propertyData = $request->property_for_sale->toArray();
        $propertyData['images'] = $request->property_for_sale->Property_image;
        $propertyData['documents'] = $request->property_for_sale->Property_document;
        $propertyData['id_images'] = $request->property_for_sale->id_image;

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => array_merge($additionalData,$propertyData)
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
