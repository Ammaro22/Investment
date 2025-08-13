<?php

namespace App\Http\Controllers\v1;


use App\Models\Deputization;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeputizationController extends Controller
{
    public function createDeputization(Request $request)
    {
        $userid = Auth::id();

        $validator = Validator::make($request->all(), [
            'ID_Number' => 'required|max:50',
            'deputization_Content' => 'required|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
        $deputization = Deputization::create([
            'user_id' =>$userid,
            'ID_Number' => $request->ID_Number,
            'deputization_Content' => $request->deputization_Content,
            'status'=>'Stuck',
        ]);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $deputization
        ]);
    }
}
