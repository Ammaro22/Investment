<?php

namespace App\Http\Controllers\v1;


use App\Models\Agreed_negotiation;
use App\Models\Property_for_sale;
use App\Models\request_from_lawyer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class AgreedNegotiationController extends Controller
{

    public function createAgreedNegotiation(Request $request)
    {   $user=auth()->user();
        $userRole = $user->role_id;
        if (!$user|$userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(),[
            'Text_of_the_agreement' => 'required|string',
            'property_for_sale_id' => 'required|exists:property_for_sales,id',
            'Payment_Mechanism' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
        $negotiation = new Agreed_negotiation();
        $negotiation->expert_id=$user->id;
        $negotiation->Text_of_the_agreement = $request->Text_of_the_agreement;
        $negotiation->Payment_Mechanism = $request->Payment_Mechanism;
        $negotiation->status = 'معلق';
        $negotiation->property_for_sale_id = $request->property_for_sale_id;
        $negotiation->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $negotiation,
        ], 201);
    }

    public function updateAgreedNegotiation(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3 && $userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'Text_of_the_agreement' => 'required|string',
            'Payment_Mechanism' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $negotiation = Agreed_negotiation::find($id);
        if (!$negotiation) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $negotiation->Text_of_the_agreement = $request->Text_of_the_agreement;
        $negotiation->Payment_Mechanism = $request->Payment_Mechanism;
        $negotiation->status = 'تم قبول من قبل المستخدم';
        $negotiation->save();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $negotiation,
        ], 200);
    }

    public function rejectAgreedNegotiation($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 2 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $negotiation = Agreed_negotiation::find($id);
        if (!$negotiation) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $negotiation->status = 'تم الرفض من قبل المستخدد';
        $negotiation->save();

        request_from_lawyer::where('property_for_sale_id', $negotiation->property_for_sale_id)
            ->update(['accept_user' => 'مرفوض']);

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $negotiation,
        ], 200);
    }

    public function acceptAgreedNegotiation($id)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 2 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $negotiation = Agreed_negotiation::find($id);
        if (!$negotiation) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $negotiation->status =  'تم قبول الطلب من قبل المستخدم';
        $negotiation->save();

        request_from_lawyer::where('property_for_sale_id', $negotiation->property_for_sale_id)
            ->update(['accept_user' => 'مقبول']);

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $negotiation,
        ], 200);
    }

    public function getAgreedNegotiationsByPropertyId($propertyId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $negotiations = Agreed_negotiation::where('property_for_sale_id', $propertyId)->get();

        if ($negotiations->isEmpty()) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $negotiations,
        ], 200);
    }

    public function getAgreedNegotiationsForUser(Request $request)
    {
        $user = $request->user();
        $properties = Property_for_sale::where('user_id', $user->id)->pluck('id');

        $negotiations = Agreed_negotiation::with('expert')->whereIn('property_for_sale_id', $properties)
            ->whereHas('expert',function ($query)
            {
                $query->where('role_id',3);
            })->get();

        $results=$negotiations->map(function ($negotiation)
        {
            $info=$negotiation->expert??null;
            return[
                'id'=>$negotiation->id,
                'expert_id'=>$info->id,
                'expert_name'=>$info->name,
                'property_for_sale_id'=>$negotiation->property_for_sale_id,
                'Text_of_the_agreement'=>$negotiation->Text_of_the_agreement,
                'Payment_Mechanism'=>$negotiation->Payment_Mechanism,
                'status'=>$negotiation->status,
                'created_at'=>now()->format('Y-m-d'),
                'updated_at'=>now()->format('Y-m-d')
            ];
        });

        if ($negotiations->isEmpty()) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $results,
        ], 200);
    }

}
