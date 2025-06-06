<?php

namespace App\Http\Controllers;

use App\Models\AmountInvested;
use App\Models\Property_for_sale;
use App\Models\request_from_lawyer;
use App\Models\Reward;
use App\Models\RewardTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;


class RewardController extends Controller
{

    public function addReward(Request $request)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(),[
            'amount_threshold' => 'required|numeric|min:1',
            'percentage' => 'required|numeric|min:1|max:100',
            'level' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $reward = Reward::create([
            'amount_threshold' => $request->input('amount_threshold'),
            'percentage' => $request->input('percentage'),
            'level' => $request->input('level'),
        ]);

        return response()->json([
            'message' => 'Reward added successfully.',
            'data' => $reward,
        ], 201);

}

    public function updateReward(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $reward = Reward::find($id);
        if (!$reward) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }

        $request->validate([
            'amount_threshold' => 'sometimes|numeric|min:1',
            'percentage' => 'sometimes|numeric|min:1|max:100',
            'level' => 'sometimes',
        ]);

        $reward->update($request->only(['amount_threshold', 'percentage', 'level']));

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $reward,
        ], 200);
    }

    public function getRewards(Request $request)
    {
        $reward = Reward::all();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $reward,
        ], 200);
    }

    public function getUserInvestmentsAndRewards(Request $request)
    {
        $userId = auth()->user()->id;

        $investedAmount = AmountInvested::where('user_id', $userId)->sum('amount_invested');

        $rewards = RewardTransactions::where('user_id', $userId)
            ->with('reward')
            ->get();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'invested_amount' => $investedAmount,
                'rewards' => $rewards,
            ],
        ], 200);
    }


    public function deleteReward($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $reward = Reward::find($id);
        if (!$reward) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }

        $reward->delete();

        return response()->json([
            'message' => trans('messages.delete_success'),
        ], 200);
    }

    public function showLargestReward()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => trans('messages.unauthorized')], 401);
        }

        $largestReward = RewardTransactions::with('reward')
            ->where('user_id', $user->id)
            ->orderBy('amount_profit', 'desc')
            ->first();

        if (!$largestReward) {
            return response()->json(['message' => trans('messages.not_found')]);
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'reward_id' => $largestReward->reward_id,
                'amount_profit' => $largestReward->amount_profit,
                'level' => $largestReward->reward->level,
            ]
        ]);
    }

    public function markAsSold($propertyId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

            $property = Property_for_sale::find($propertyId);

            if (!$property) {
              return response()->json(['message' => trans('messages.not_found')]);

            }
            $property->update([
                'status' => 'تم البيع',
            ]);

            return response()->json([
                'message' => trans('messages.operation_success'),
                'data' => $property
            ]);
    }

    public function createLawyerRequest(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'property_for_sale_id' => 'required|exists:property_for_sales,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

            $lawyerRequest = request_from_lawyer::create([
                'property_for_sale_id' => $request->property_for_sale_id,
                'status' => 'معلق',
                'accept_user' => 'مقبول',
                'accept_admin' => 'معلق'
            ]);

            return response()->json([
                'message' => trans('messages.operation_success'),
                'data' => $lawyerRequest
            ], 201);


    }

}
