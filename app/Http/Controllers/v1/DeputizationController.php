<?php

namespace App\Http\Controllers\v1;


use App\Models\Deputization;
use App\Models\Investment;
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
            'deputization_Content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
        $deputization = Deputization::create([
            'user_id' => $userid,
            'ID_Number' => $request->ID_Number,
            'deputization_Content' => $request->deputization_Content,
            'status' => 'Stuck',

        ]);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $deputization
        ]);
    }

    public function getDeputizationsWithUsers()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 4) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $deputizations = Deputization::with('user')->paginate(10);

        $formattedDeputizations = $deputizations->map(function ($deputization) {
            return [
                'id' => $deputization->id,
                'user_id' => $deputization->user_id,
                'user_name' => $deputization->user ? $deputization->user->name : 'Unknown',
                'ID_Number' => $deputization->ID_Number,
                'deputization_Content' => $deputization->deputization_Content,
                'deputization_image' => $deputization->deputization_image,
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

    public function getDeputizationsforUser()
    {
        $user = auth()->user();

        $deputizations = Deputization::where('user_id', $user->id)
            ->with('user')
            ->paginate(6);

        $formattedDeputizations = $deputizations->map(function ($deputization) {
            return [
                'id' => $deputization->id,
                'user_id' => $deputization->user_id,
                'user_name' => $deputization->user ? $deputization->user->name : 'Unknown',
                'ID_Number' => $deputization->ID_Number,
                'deputization_Content' => $deputization->deputization_Content,
                'deputization_image' => $deputization->deputization_image,
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

    public function acceptDeputization(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 4) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'deputization_image' => 'max:3000',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $deputization = Deputization::findOrFail($id);

        if (!$deputization) {
            return response()->json(['message' => trans('messages.not_found')], 404);
        }


        $deputizationImagePath = null;
        if (request()->hasFile('deputization_image')) {
            $deputizationImage = request()->file('deputization_image');
            $deputizationImageName = time() . '_deputization_' . $deputizationImage->getClientOriginalName();
            $deputizationImage->move(public_path('images/deputizations'), $deputizationImageName);
            $deputizationImagePath = "images/deputizations/$deputizationImageName";
        }

            $deputization->update([
                'status' => 'Processed',
                'deputization_image' => $deputizationImagePath
            ]);

        $userId = $deputization->user_id;
        Investment::where('user_id', $userId)
            ->update(['Acceptable' => 1]);


        return response()->json([
                'message' => trans('messages.operation_success'),
                'data' => $deputization
            ]);
        }

    }

