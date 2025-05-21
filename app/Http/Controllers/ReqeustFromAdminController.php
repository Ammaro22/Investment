<?php

namespace App\Http\Controllers;

use App\Models\Agreed_negotiation;
use App\Models\EconomicEvaluation;
use App\Models\Electronic_Property_Certificate;
use App\Models\Property_for_sale;
use App\Models\Request_from_admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReqeustFromAdminController extends Controller
{
    public function addImagesAndCompleteRequest(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $requestFromAdmin = Request_from_admin::find($id);
        if (!$requestFromAdmin) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $validated = $request->validate([
            'front_image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'back_image' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        $frontImage = $request->file('front_image');
        $frontImageName = time() . '_' . $frontImage->getClientOriginalName();
        $frontImage->move(public_path('images/front'), $frontImageName);
        $frontImagePath = "images/front/$frontImageName";

        $backImage = $request->file('back_image');
        $backImageName = time() . '_' . $backImage->getClientOriginalName();
        $backImage->move(public_path('images/back'), $backImageName);
        $backImagePath = "images/back/$backImageName";

        $requestFromAdmin->front_image = $frontImagePath;
        $requestFromAdmin->back_image = $backImagePath;
        $requestFromAdmin->status = 'Completed';
        $requestFromAdmin->save();

        $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $requestFromAdmin->property_for_sale_id)->first();
        $property = Property_for_sale::find($requestFromAdmin->property_for_sale_id);
        $user = User::find($property->user_id);
        $agreedNegotiationId = $economicEvaluation->agreed_negotiations_id;

        $agreedNegotiation = Agreed_negotiation::find($agreedNegotiationId);
        $paymentMethod = $agreedNegotiation ? $agreedNegotiation->Payment_Mechanism : $property->pay_way;

        $electronicCertificate = new Electronic_Property_Certificate();
        $electronicCertificate->request_from_admin_id = $requestFromAdmin->id;
        $electronicCertificate->Seller_name = $user->name;
        $electronicCertificate->Property_location = $property->state . ', ' . $property->exact_position;
        $electronicCertificate->lawyer_name = auth()->user()->name;
        $electronicCertificate->Property_Price = $economicEvaluation->expected_price;
        $electronicCertificate->Payment_method = $paymentMethod;
        $electronicCertificate->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $requestFromAdmin,
        ], 200);
    }

    public function getCompletedRequests()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $completedRequests = Request_from_admin::with('proprtsseale:id,state,exact_position')
        ->where('status', 'completed')
            ->get();

        if ($completedRequests->isEmpty()) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }


        $requestsData = $completedRequests->map(function ($request) {
            return [
                'id' => $request->id,
                'status' => $request->status,
                'type_request' => $request->type_request,
                'property' => $request->proprtsseale,
            ];
        });

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $requestsData,
        ], 200);
    }

    public function getRequestWithImages($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $request = Request_from_admin::with('proprtsseale:id,state,exact_position')
        ->find($id);

        if (!$request) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $responseData = [
            'id' => $request->id,
            'status' => $request->status,
            'type_request' => $request->type_request,
            'front_image' => $request->front_image,
            'back_image' => $request->back_image,
            'property' => $request->proprtsseale,
        ];

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $responseData,
        ], 200);
    }

}
