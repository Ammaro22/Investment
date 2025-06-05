<?php

namespace App\Http\Controllers;


use App\Models\Investment;

use App\Models\request_from_expert;
use App\Models\request_from_lawyer;
use App\Models\Requests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{

    /*نسبة الطلبات التي تم رفضها من المحامي*/
    public function rejectedRequestsPercentageformlawyer(Request $request)
    {
        $year = $request->input('year');
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $totalRequests = Requests::whereYear('created_at', $year)->count();
        $rejectedRequests = Requests::whereYear('created_at', $year)
            ->where('status', 'مرفوض')
            ->count();

        $percentage = $totalRequests > 0 ? ($rejectedRequests / $totalRequests) * 100 : 0;

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $percentage]);
    }

    /*نسبة الطلبات التي تم مقول من الادمن*/
    public function acceptedRequestsPercentagefromadmin(Request $request)
    {
        $year = $request->input('year');
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $totalRequests = request_from_lawyer::whereYear('created_at', $year)->count();

        $acceptedRequests = request_from_lawyer::whereYear('created_at', $year)
            ->where('accept_admin', 'مقبول')
            ->count();

        $percentage = $totalRequests > 0 ? ($acceptedRequests / $totalRequests) * 100 : 0;
        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $percentage]);
    }
    /*نسبة الطلبات التي تم رفض من المستخدم*/
    public function rejectedRequestsPercentagefromuser(Request $request)
    {
        $year = $request->input('year');

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 3 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $totalRequests = request_from_lawyer::whereYear('created_at', $year)->count();

        $rejectedRequests = request_from_lawyer::whereYear('created_at', $year)
            ->where('accept_user', 'مرفوض')
            ->count();


        $percentage = $totalRequests > 0 ? ($rejectedRequests / $totalRequests) * 100 : 0;

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $percentage]);
    }

    /*نسبة العقارات التي تم الموافقة عليها من العقارات المقدمة للمنصة*/

    public function successfulRequestsPercentageByMonth(Request $request)
    {
        $year = $request->input('year');

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $totalRequests = request_from_lawyer::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $successfulRequests = request_from_lawyer::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('accept_user', 'مقبول')
                ->whereHas('property_for_sale', function ($query) {
                    $query->where('legal_check', 1)
                        ->where('expert_check', 1)
                        ->where('accept', 1);
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('request_from_admins')
                        ->whereColumn('request_from_admins.property_for_sale_id', 'request_from_lawyers.property_for_sale_id')
                        ->where('request_from_admins.status', 'Completed');
                })
                ->count();

            $percentage = $totalRequests > 0 ? ($successfulRequests / $totalRequests) * 100 : 0;

            $months[$month] = $percentage;
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $months
        ]);
    }

    /*عدد الفرص وسعرهم للمستخدم خلال الشهر ضمن سنة*/
    public function getInvestmentsByMonthAndYear(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        $userId = auth()->user()->id;
        // الحصول على الاستثمارات للمستخدم في الشهر والسنة المحددين
        $investments = Investment::where('user_id', $userId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->get();

        // حساب عدد الفرص وسعرها
        $totalOpportunities = $investments->sum('chance_invested');
        $totalAmount = $investments->sum('amount_payed');

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'total_opportunities' => $totalOpportunities,
                'total_amount' => $totalAmount,
            ]
        ]);
    }
}
