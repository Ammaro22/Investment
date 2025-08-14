<?php

namespace App\Http\Controllers\v1;


use App\Models\Investment;
use App\Models\InvestmentCertificate;
use App\Models\RequestForOwnership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvestmentCertificateController extends Controller
{
    public function getInvestmentCertificates(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => trans('messages.unauthorized')], 401);
        }

        $certificates = InvestmentCertificate::with('Investment.property_invested')
            ->where('user_id', $user->id)
            ->get();

        $certificateDetails = $certificates->map(function ($certificate) {
            return [
                'user_name' => $certificate->user->name,
                'investment_id' => $certificate->investment_id,
                'property_location' => $certificate->property_Location,
                'number_chance' => $certificate->number_chance,
                'price' => $certificate->Investment->amount_payed,
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $certificateDetails,
        ]);
    }

//    public function transferInvestmentOwnership(Request $request)
//    {
//        $user = Auth::guard('api')->user();
//
//        if (!$user) {
//            return response()->json(['message' => trans('messages.unauthorized')], 401);
//        }
//
//        $request->validate([
//            'new_user_id' => 'required|exists:users,id',
//            'certificate_id'=> 'required|exists:investment_certificates,id'
//        ]);
//        $certificate_id=$request->certificate_id;
//
//        $certificate = InvestmentCertificate::find($certificate_id);
//
//        if (!$certificate) {
//            return response()->json(['message' => trans('messages.not_found')], 404);
//        }
//
//        $certificate->user_id = $request->new_user_id;
//        $certificate->save();
//
//        $investment = Investment::where('id', $certificate->investment_id)->first();
//
//        if ($investment) {
//
//            $investment->user_id = $request->new_user_id;
//            $investment->save();
//        }
//
//        return response()->json([
//            'message' => trans('messages.operation_success'),
//            'data' => [
//                'certificate' => $certificate,
//                'investment' => $investment ?? null,
//            ],
//        ]);
//    }

    public function transferInvestmentOwnership(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => trans('messages.unauthorized')], 401);
        }

        $request->validate([
            'new_user_id' => 'required|exists:users,id',
            'certificate_id' => 'required|exists:investment_certificates,id'
        ]);

        $certificate = InvestmentCertificate::with('investment')->find($request->certificate_id);

        if (!$certificate) {
            return response()->json(['message' => trans('messages.not_found')], 404);
        }

        if ($certificate->user_id != $user->id) {
            return response()->json(['message' => trans('messages.not_owner')], 403);
        }


        $taxRate = 0.005;
        $taxAmount = $certificate->investment->amount_payed * $taxRate;


        $ownershipRequest = RequestForOwnership::create([
            'investment_certificate_id' => $certificate->id,
            'seller_id' => $certificate->user_id,
            'buyer_id' => $request->new_user_id,
            'Tax' => $taxAmount,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'ownership_request' => $ownershipRequest,
                'tax_amount' => $taxAmount,
                'investment_amount' => $certificate->investment->amount_payed
            ],
        ]);
    }

    public function searchUser(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:300',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $email = $request->input('email');
        $users = User::where('email', 'LIKE', "%{$email}%")->get();
        if ($users->isEmpty()) {
            return response()->json(['message' => trans('messages.not_found')], 404);
        }
        $filteredUsers = $users->map(function ($user) {
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $filteredUsers,
        ]);
    }

    public function getOwnershipRequestsForLawyer(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $requests = RequestForOwnership::with([
            'investment_certificates',
            'seller:id,name',
            'buyer:id,name'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);


        $formattedRequests = $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'investment_certificate_id' => $request->investment_certificate_id,
                'property_location' => $request->investment_certificates->property_Location ?? null,
                'seller_id' => $request->seller_id,
                'seller_name' => $request->seller->name ?? null,
                'buyer_id' => $request->buyer_id,
                'buyer_name' => $request->buyer->name ?? null,
                'Tax' => $request->Tax,
                'status' => $request->status,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'requests' => $formattedRequests,
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                    'next_page_url' => $requests->nextPageUrl(),
                    'prev_page_url' => $requests->previousPageUrl(),
                ]
            ]
        ]);
    }

    public function approveOwnershipRequest(Request $request,$id)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $ownershipRequest = RequestForOwnership::find($id);

        if(!$ownershipRequest)
        {
            return response()->json(['message'=>'messages.not_found']);
        }

        DB::beginTransaction();

        try {
            $ownershipRequest->status = 'approved';
            $ownershipRequest->save();

            $certificate = InvestmentCertificate::find($ownershipRequest->investment_certificate_id);
            $certificate->user_id = $ownershipRequest->buyer_id;
            $certificate->save();

            if ($certificate->investment) {
                $certificate->investment->user_id = $ownershipRequest->buyer_id;
                $certificate->investment->save();
            }

            DB::commit();

            return response()->json([
                'message' => trans('messages.operation_success'),
                'data' => [
                    'request' => $ownershipRequest,
                    'certificate' => $certificate
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => trans('messages.transfer_failed')], 500);
        }
    }

}
