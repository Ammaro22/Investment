<?php

namespace App\Services;

use App\Models\EconomicEvaluation;

class PropertyAnalysisService{
    public function analyze(EconomicEvaluation $evaluation, int $threshold = 4): ?string
    {
        $indicatorValues = $evaluation->indicatorValues()->with('indicator')->get();
        $matchingCount = 0;

        foreach ($indicatorValues as $indicatorValue) {
            $value = $indicatorValue->value;
            $indicator = $indicatorValue->indicator;

            if ($value >= $indicator->recommended_min && $value <= $indicator->recommended_max) {
                $matchingCount++;
            }
        }



        return $matchingCount >= $threshold
            ? 'نوصيك باستثمار هذا العقار'
            : null;
    }


}
