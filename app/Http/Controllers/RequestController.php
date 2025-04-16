<?php

namespace App\Http\Controllers;


use App\Models\Requests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class RequestController extends Controller
{
    public function acceptRequest($id)
    {

        $userRole = auth()->user()->role_id;
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

    public function getAllRequests()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $requests = Requests::with('property_for_sale.user:id,name')->get()->map(function ($request) {
            return [
                'id' => $request->id,
                'status' => $request->status,
                'description' => $request->description,
                'user_name' => $request->property_for_sale->user->name ?? null,
                'created_at' => $request->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success')
            , 'data' => $requests]);
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
