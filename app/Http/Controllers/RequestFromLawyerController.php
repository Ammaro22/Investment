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


        $requests = request_from_lawyer::with('property_for_sale.user')->get();

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }


        $responseData = $requests->map(function ($request) {
            return [
                'request_id' => $request->id,
                'property_for_sale_id' => $request->property_for_sale_id,
                'status' => $request->status,
                'user_name' => $request->property_for_sale->user->name,
                'created_at' => $request->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

//    public function getPropertyByRequestId($id)
//    {
//        $userRole = auth()->user()->role_id;
//        if ($userRole !== 3 ) {
//            return response()->json([
//                'message' => trans('messages.unauthorized'),
//            ], 403);
//        }
//        $request = request_from_lawyer::find($id);
//
//        if (!$request) {
//            return response()->json([
//                'message' => __('messages.not_found'),
//            ], 404);
//        }
//        $property = Property_for_sale::find($request->property_for_sale_id);
//
//        if (!$property) {
//            return response()->json([
//                'message' => __('messages.property_not_found'),
//            ], 404);
//        }
//
//        return response()->json([
//            'message' => __('messages.operation_success'),
//            'data' => $property,
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

        $request = request_from_lawyer::find($id);

        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $property = Property_for_sale::with('Property_image', 'Property_document', 'id_image')->find($request->property_for_sale_id);

        if (!$property) {
            return response()->json([
                'message' => __('messages.property_not_found'),
            ], 404);
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $property,
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
