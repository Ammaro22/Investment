<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Indicator;

class IndicatorSeeder extends Seeder
{
    public function run()
    {
        $indicators = [
            [
                'name' => 'Residential Cap Rate',
                'arabic_name' => 'نسبة العائد السكني',
                'recommended_min' => 3.5,
                'recommended_max' => 5.5,
            ],
            [
                'name' => 'Commercial Cap Rate',
                'arabic_name' => 'نسبة العائد التجاري',
                'recommended_min' => 6,
                'recommended_max' => 9,
            ],
            [
                'name' => 'Cash on Cash Return',
                'arabic_name' => 'العائد النقدي على النقد',
                'recommended_min' => 5.5,
                'recommended_max' => 11,
            ],
            [
                'name' => 'Internal Rate of Return',
                'arabic_name' => 'معدل العائد الداخلي',
                'recommended_min' => 10,
                'recommended_max' => 16.5,
            ],
            [
                'name' => 'Vacancy Rate',
                'arabic_name' => 'نسبة الشغور',
                'recommended_min' => 6.5,
                'recommended_max' => 12.5,
            ],
            [
                'name' => 'Operating Expense Ratio',
                'arabic_name' => 'نسبة المصاريف التشغيلية',
                'recommended_min' => 27.5,
                'recommended_max' => 42.5,
            ],
        ];

        foreach ($indicators as $indicator) {
            Indicator::create($indicator);
        }
    }
}
