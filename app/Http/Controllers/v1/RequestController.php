<?php

namespace App\Http\Controllers\v1;


use App\Models\Property_for_sale;
use App\Models\Request_from_admin;
use App\Models\request_from_lawyer;
use App\Models\Requests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class RequestController extends BaseController
{
    public function acceptRequest($id)
    {
        $user=auth()->user();
        $userRole = $user->role_id;
        if ($userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $request = Requests::find($id);
        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $request->status = 'مقبول';
        $request->description = 'تم قبول الطلب قانونياً';
        $request->save();

        $property = Property_for_sale::find($request->property_for_sale_id);
        if ($property) {
            $property->legal_check = true;
            $property->save();
        }

        $lawyerRequest = new request_from_lawyer();
        $lawyerRequest->property_for_sale_id = $request->property_for_sale_id;
        $lawyerRequest->status = 'معلق';
        $lawyerRequest->accept_user = 'معلق';
        $lawyerRequest->accept_admin = 'معلق';
        $lawyerRequest->by_whom = 'lawyer';
        $lawyerRequest->save();


        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $request]);
    }

    public function rejectRequest(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(),[

                'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $requestToReject = Requests::find($id);


        if (!$requestToReject) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $requestToReject->status = 'مرفوض';
        $requestToReject->description = $request->description;

        $requestToReject->save();

        return response()->json([
            'message' => trans('messages.operation_success')
            , 'data' => $requestToReject]);
    }

    public function getSeparatedRequests()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }


        $requests = Requests::with(['property_for_sale.user:id,name'])->get()->map(function ($request) {
            return [
                'id' => $request->id,
                'status' => $request->status,
                'description' => $request->description,
                'user_name' => $request->property_for_sale->user->name ?? null,
                'property_type' => $request->property_for_sale->property_type ?? null,
                'location' => trim($request->property_for_sale->state . ' ' . $request->property_for_sale->exact_position),
                'created_at' => $request->created_at->format('Y-m-d H:i:s')
            ];
        });

        $adminRequests = Request_from_admin::with(['proprtsseale.user:id,name'])->get()->map(function ($adminRequest) {
            return [
                'id' => $adminRequest->id,
                'status' => $adminRequest->status,
                'type_request' => $adminRequest->type_request,
                'user_name' => $adminRequest->proprtsseale->user->name ?? null,
                'property_type' => $adminRequest->proprtsseale->property_type ?? null,
                'location' => trim($adminRequest->proprtsseale->state . ' ' . $adminRequest->proprtsseale->exact_position),
                'created_at' => $adminRequest->created_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'legal check' => $requests,
                'buy requests' => $adminRequests,
            ],
        ]);
    }

    public function getMyRequests()
    {
        $user = Auth::user();
        $requests = Requests::whereHas('property_for_sale', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get()->map(function ($request) {
            return [
                'id' => $request->id,
                'status' => $request->status,
                'description' => $request->description,
                'created_at' => $request->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success')
            , 'data' => $requests]);
    }



}
