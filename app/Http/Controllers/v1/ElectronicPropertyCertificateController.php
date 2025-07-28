<?php

namespace App\Http\Controllers\v1;


use App\Models\Electronic_Property_Certificate;
use App\Models\Property_for_sale;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ElectronicPropertyCertificateController extends Controller
{
    public function showCertificates(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $certificates = Electronic_Property_Certificate::with('request_from_admin')->get();

        return response()->json([
            'message' => __('messages.certificates_fetched_successfully'),
            'data' => $certificates,
        ], 200);
    }

    public function showUserCertificates(Request $request)
    {

        $user = auth()->user();

        $properties = Property_for_sale::where('user_id', $user->id)->pluck('id');


        $certificates = Electronic_Property_Certificate::whereIn('request_from_admin_id', function($query) use ($properties) {
            $query->select('id')->from('request_from_admins')->whereIn('property_for_sale_id', $properties);
        })->with('request_from_admin')->get();


        return response()->json([
            'message' => __('messages.certificates_fetched_successfully'),
            'data' => $certificates,
        ], 200);
    }

}
