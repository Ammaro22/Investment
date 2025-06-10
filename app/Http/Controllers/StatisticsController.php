<?php

namespace App\Http\Controllers;


use App\Models\Investment;

use App\Models\Profit;
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


    public function getRequestStatistics(Request $request)
    {
        $year = $request->input('year');

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 && $userRole !== 3) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        // Calculate all statistics
        $rejectedByLawyer = $this->calculatePercentage(
            Requests::whereYear('created_at', $year)->count(),
            Requests::whereYear('created_at', $year)->where('status', 'مرفوض')->count()
        );

        $acceptedByAdmin = $this->calculatePercentage(
            request_from_lawyer::whereYear('created_at', $year)->count(),
            request_from_lawyer::whereYear('created_at', $year)->where('accept_admin', 'مقبول')->count()
        );

        $rejectedByUser = $this->calculatePercentage(
            request_from_lawyer::whereYear('created_at', $year)->count(),
            request_from_lawyer::whereYear('created_at', $year)->where('accept_user', 'مرفوض')->count()
        );

        return response()->json([
            'message' => trans('messages.operation_success'),
            'rejected_by_lawyer_percentage' => $rejectedByLawyer,
            'accepted_by_admin_percentage' => $acceptedByAdmin,
            'rejected_by_user_percentage' => $rejectedByUser,
            'year' => $year
        ]);
    }

    /**
     * Helper method to calculate percentage
     */
    private function calculatePercentage($total, $filtered)
    {
        return $total > 0 ? round(($filtered / $total) * 100, 2) : 0;
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

    /*نسبة الاستثمار  */

//    public function getInvestmentPercentageByMonth(Request $request)
//    {
//
//        $user = $request->user();
//
//
//        $request->validate([
//            'year' => 'required|integer|min:2000|max:' . now()->year,
//        ]);
//
//        // الحصول على السنة المطلوبة
//        $requestedYear = $request->input('year');
//
//
//        if (!$user) {
//            return response()->json(['error' => 'User not found'], 404);
//        }
//
//        // الحصول على الاستثمارات الخاصة بالمستخدم للسنة المطلوبة
//        $investments = Investment::where('user_id', $user->id)
//            ->whereYear('created_at', $requestedYear)
//            ->get();
//
//        // تهيئة مصفوفة للأشهر
//        $months = [];
//        for ($month = 1; $month <= 12; $month++) {
//            $months[$month] = [
//                'total_investment' => 0,
//            ];
//        }
//
//        // جمع الاستثمارات حسب الشهر
//        foreach ($investments as $investment) {
//            $month = $investment->created_at->month; // الشهر من 1 إلى 12
//            $months[$month]['total_investment'] += $investment->amount_payed;
//        }
//
//        // حساب إجمالي الاستثمارات للسنة
//        $totalYearlyInvestment = array_sum(array_column($months, 'total_investment'));
//
//        // حساب نسبة الاستثمار لكل شهر من إجمالي السنة وإزالة الأشهر التي ليس لديها استثمار
//        $investmentPercentages = [];
//        foreach ($months as $month => $data) {
//            if ($data['total_investment'] >= 0 && $totalYearlyInvestment >= 0) {
//                $investmentPercentages[$month] = ($data['total_investment'] / $totalYearlyInvestment) * 100;
//            }
//        }
//
//        return response()->json([
//            'message' => trans('messages.operation_success'),
//            'data' => $investmentPercentages
//        ]);
//    }

    public function getInvestmentPercentageByMonth(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'year' => 'required|integer|min:2000|max:' . now()->year,
        ]);

        // الحصول على السنة المطلوبة
        $requestedYear = $request->input('year');

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // الحصول على الاستثمارات الخاصة بالمستخدم للسنة المطلوبة
        $investments = Investment::where('user_id', $user->id)
            ->whereYear('created_at', $requestedYear)
            ->get();

        // تهيئة مصفوفة للأشهر
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'total_investment' => 0,
            ];
        }

        // جمع الاستثمارات حسب الشهر
        foreach ($investments as $investment) {
            $month = $investment->created_at->month; // الشهر من 1 إلى 12
            $months[$month]['total_investment'] += $investment->amount_payed;
        }

        // حساب إجمالي الاستثمارات للسنة
        $totalYearlyInvestment = array_sum(array_column($months, 'total_investment'));

        // التحقق من أن إجمالي الاستثمار السنوي ليس صفرًا لتجنب القسمة على صفر
        if ($totalYearlyInvestment == 0) {
            return response()->json([
                'message' => __('messages.operation_success'),
                'data' => [],
                'warning' => 'No investments found for the specified year',
            ], 200);
        }

        // حساب نسبة الاستثمار لكل شهر من إجمالي السنة
        $investmentPercentages = [];
        foreach ($months as $month => $data) {
            if ($data['total_investment'] > 0) {
                $investmentPercentages[$month] = ($data['total_investment'] / $totalYearlyInvestment) * 100;
            } else {
                $investmentPercentages[$month] = 0;
            }
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $investmentPercentages
        ], 200);
    }

/*مسبة ارباح المستخدم في السنة*/

//    public function getProfitPercentageByMonth(Request $request)
//    {
//        $user = $request->user();
//
//        $request->validate([
//            'year' => 'required|integer|min:2000|max:' . now()->year,
//        ]);
//
//        $requestedYear = $request->input('year');
//
//        if (!$user) {
//            return response()->json(['error' => 'User not found'], 404);
//        }
//
//        $profits = Profit::where('user_id', $user->id)
//            ->whereYear('scheduled_date', $requestedYear)
//            ->get();
//
//        $months = [];
//        for ($month = 1; $month <= 12; $month++) {
//            $months[$month] = [
//                'total_profit' => 0,
//            ];
//        }
//
//        foreach ($profits as $profit) {
//            // تأكد من تحويل scheduled_date إلى كائن Carbon
//            $month = \Carbon\Carbon::parse($profit->scheduled_date)->month;
//            $months[$month]['total_profit'] += $profit->profit_amount;
//        }
//
//        $totalYearlyProfit = array_sum(array_column($months, 'total_profit'));
//
//        $profitPercentages = [];
//        foreach ($months as $month => $data) {
//            if ($data['total_profit'] >= 0 && $totalYearlyProfit >= 0) {
//                $profitPercentages[$month] = ($data['total_profit'] / $totalYearlyProfit) * 100;
//            }
//        }
//
//        return response()->json([
//            'message' => trans('messages.operation_success'),
//            'data' => $profitPercentages
//        ]);
//    }

    public function getProfitPercentageByMonth(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'year' => 'required|integer|min:2000|max:' . now()->year,
        ]);

        $requestedYear = $request->input('year');

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $profits = Profit::where('user_id', $user->id)
            ->whereYear('scheduled_date', $requestedYear)
            ->get();

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'total_profit' => 0,
            ];
        }

        foreach ($profits as $profit) {
            // تحويل scheduled_date إلى كائن Carbon
            $month = \Carbon\Carbon::parse($profit->scheduled_date)->month;
            $months[$month]['total_profit'] += $profit->profit_amount;
        }

        // حساب إجمالي الأرباح للسنة
        $totalYearlyProfit = array_sum(array_column($months, 'total_profit'));

        // التحقق من أن إجمالي الأرباح ليس صفرًا لتجنب القسمة على صفر
        if ($totalYearlyProfit == 0) {
            return response()->json([
                'message' => __('messages.operation_success'),
                'data' => [],
                'warning' => 'No profits recorded for the specified year',
            ], 200);
        }

        // حساب نسبة الربح لكل شهر
        $profitPercentages = [];
        foreach ($months as $month => $data) {
            if ($data['total_profit'] > 0) {
                $profitPercentages[$month] = ($data['total_profit'] / $totalYearlyProfit) * 100;
            } else {
                $profitPercentages[$month] = 0;
            }
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $profitPercentages
        ], 200);
    }


}
