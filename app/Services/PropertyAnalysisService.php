<?php

namespace App\Services;

use App\Models\EconomicEvaluation;

class PropertyAnalysisService{

    public function analyze(EconomicEvaluation $evaluation): array
    {
        $indicatorValues = $evaluation->indicatorValues()->with('indicator')->get();
        $recommendations = [];

        foreach ($indicatorValues as $indicatorValue) {
            $value = $indicatorValue->value;
            $indicator = $indicatorValue->indicator;

            if ($value >= $indicator->recommended_min && $value <= $indicator->recommended_max) {
                $recommendations[] = "نوصيك باستثمار هذا العقار بناءً على مؤشر {$indicator->arabic_name}.";
//            } elseif ($value > $indicator->recommended_max) {
//                $recommendations[] = "لا ينصح باستثمار هذا العقار بسبب ارتفاع قيمة {$indicator->name}.";
//            } elseif ($value < $indicator->recommended_min) {
//                $recommendations[] = "لا ينصح باستثمار هذا العقار بسبب انخفاض قيمة {$indicator->name}.";
//            }
        }
        }
        return $recommendations;
    }

    public static function calculateValue($indicator, $data)
    {
        // مثال على كيفية تطبيق القوانين لحساب القيمة
        switch ($indicator->name) {
            case 'Residential Cap Rate':
                if (!isset($data['net_operating_income']) || !isset($data['property_value'])) {
                    return ['error' => __('messages.indicator')];
                }
                return ($data['net_operating_income'] / $data['property_value']) * 100;

            case 'Commercial Cap Rate':
                if (!isset($data['net_operating_income']) || !isset($data['property_value'])) {
                    return ['error' => __('messages.indicator')];
                }
                return ($data['net_operating_income'] / $data['property_value']) * 100;

            case 'Cash on Cash Return':
                if (!isset($data['annual_cash_flow']) || !isset($data['cash_invested'])) {
                    return ['error' => __('messages.indicator')];
                }
                return ($data['annual_cash_flow'] / $data['cash_invested']) * 100;

            case 'Internal Rate of Return':
                if (!isset($data['cash_flows'])) {
                    return ['error' => __('messages.indicator')];
                }
                return self::calculateIRR($data['cash_flows']);

            case 'Vacancy Rate':
                if (!isset($data['vacant_units']) || !isset($data['total_units'])) {
                    return ['error' => __('messages.indicator')];
                }
                return ($data['vacant_units'] / $data['total_units']) * 100;

            case 'Operating Expense Ratio':
                if (!isset($data['operating_expenses']) || !isset($data['net_operating_income'])) {
                    return ['error' => __('messages.indicator')];
                }
                return ($data['operating_expenses'] / $data['net_operating_income']) * 100;

            default:
                return ['error' => 'Invalid indicator selected.'];
        }
    }

    private static function calculateIRR($cashFlows)
    {
        $maxIterations = 1000; // عدد التكرارات
        $tolerance = 0.0001; // مدى الدقة
        $rate = 0.1; // تقدير أولي لمعدل العائد
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $npv = 0;
            foreach ($cashFlows as $t => $cashFlow) {
                $npv += $cashFlow / pow((1 + $rate), $t);
            }

            // إذا كانت NPV قريبة من الصفر، نخرج من الحلقة
            if (abs($npv) < $tolerance) {
                return $rate;
            }

            // حساب المشتق (Derivative)
            $npvDerivative = 0;
            foreach ($cashFlows as $t => $cashFlow) {
                $npvDerivative -= $t * $cashFlow / pow((1 + $rate), $t + 1);
            }

            // تحديث تقدير معدل العائد باستخدام نيوتن-رافسون
            $rate = $rate - ($npv / $npvDerivative);
            $iteration++;
        }

        return null; // إذا لم يتم الوصول إلى نتيجة
    }

}
