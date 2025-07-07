<?php

namespace App\Http\Controllers;

use App\Models\AutomaticInvestment;
use App\Services\AutomaticInvestmentService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AutomaticInvestmentController extends Controller
{
    protected $investmentService;

    public function __construct(AutomaticInvestmentService $investmentService, FirebaseNotificationService $notificationService)
    {
        $this->investmentService = $investmentService;
    }

    public function activate(Request $request)
    {
        $request->validate([
            'investment_amount' => 'required|numeric|min:0',
            'investment_mode' => 'required|string',
            'expected_profit_min' => 'required|numeric|min:0',
            'expected_profit_max' => 'required|numeric|min:0',
            'min_chance_invested' => 'required|integer|min:0',
            'max_chance_invested' => 'required|integer|min:0',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'يجب تسجيل الدخول لتفعيل الاستثمار التلقائي'], 401);
        }

        $result = $this->investmentService->activateAutomaticInvestment(
            $user,
            $request->investment_amount,
            $request->investment_mode,
            [
                'min' => $request->expected_profit_min,
                'max' => $request->expected_profit_max,
            ],
            [
                'min_chance' => $request->min_chance_invested,
                'max_chance' => $request->max_chance_invested,
            ]
        );

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json([
            'message' => 'تم تفعيل الاستثمار التلقائي بنجاح',
            'data' => $result,
        ], 200);
    }

    public function deactivate(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'يجب تسجيل الدخول لإلغاء الاستثمار التلقائي'], 401);
        }

        $autoInvestment = AutomaticInvestment::where('user_id', $user->id)->first();

        if (!$autoInvestment) {
            return response()->json(['error' => 'لا يوجد استثمار تلقائي مفعّل لهذا المستخدم'], 404);
        }

        $autoInvestment->update(['active' => false]);

        return response()->json(['message' => 'تم إلغاء تنشيط الاستثمار التلقائي بنجاح'], 200);
    }

}
