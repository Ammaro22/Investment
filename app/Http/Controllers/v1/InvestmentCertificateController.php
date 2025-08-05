<?php

namespace App\Http\Controllers\v1;


use App\Models\Investment;
use App\Models\InvestmentCertificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
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

    public function transferInvestmentOwnership(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => trans('messages.unauthorized')], 401);
        }

        $request->validate([
            'new_user_id' => 'required|exists:users,id',
            'certificate_id'=> 'required|exists:investment_certificates,id'
        ]);
        $certificate_id=$request->certificate_id;

        $certificate = InvestmentCertificate::find($certificate_id);

        if (!$certificate) {
            return response()->json(['message' => trans('messages.not_found')], 404);
        }

        $certificate->user_id = $request->new_user_id;
        $certificate->save();

        $investment = Investment::where('id', $certificate->investment_id)->first();

        if ($investment) {

            $investment->user_id = $request->new_user_id;
            $investment->save();
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'certificate' => $certificate,
                'investment' => $investment ?? null,
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
            'message' => trans('messages.users_found'),
            'data' => $filteredUsers,
        ]);
    }


}
