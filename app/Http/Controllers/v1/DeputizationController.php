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

    public function getDeputizationsWithUsers()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $deputizations = Deputization::with('user')->paginate(5);

        $formattedDeputizations = $deputizations->map(function ($deputization) {
            return [
                'id' => $deputization->id,
                'user_id' => $deputization->user_id,
                'user_name' => $deputization->user ? $deputization->user->name : 'Unknown',
                'ID_Number' => $deputization->ID_Number,
                'deputization_Content' => $deputization->deputization_Content,
                'status' => $deputization->status,
                'created_at' => $deputization->created_at,
                'updated_at' => $deputization->updated_at,

            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'deputizations' => $formattedDeputizations,
                'pagination' => [
                    'current_page' => $deputizations->currentPage(),
                    'last_page' => $deputizations->lastPage(),
                    'per_page' => $deputizations->perPage(),
                    'total' => $deputizations->total(),
                    'next_page_url' => $deputizations->nextPageUrl(),
                    'prev_page_url' => $deputizations->previousPageUrl(),
                ]
            ]
        ]);
    }

    public function acceptDeputization($id)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 4 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

            $deputization = Deputization::findOrFail($id);

        if(!$deputization)
        {
            return response()->json(['message'=>'messages.not_found']);
        }


        $deputization->update(['status' => 'Processed']);

            return response()->json([
                'message' => trans('messages.operation_success'),
                'data' => $deputization
            ]);



    }
}
