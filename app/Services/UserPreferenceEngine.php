<?php
namespace App\Services;

use App\Models\User;
use App\Models\Property_for_sale;

class UserPreferenceEngine{

public function getRecommendationForInvestment(?User $user,Property_for_sale $property_for_sale):array{

//1

    if(!$user){
        return [];
    }



       $investmentsInSameArea = $user->investment()
         ->with('property_invested.property')
         ->get()
         ->filter(fn($investment) => $investment->property_invested->property->state === $property_for_sale->state);

    if ($investmentsInSameArea->count() > 1) {

        $recommendations[] = "ننصحك بتنويع استثمارك خارج منطقة {$property_for_sale->state} لتقليل المخاطر.";
    }




//2
//.اذا استثمر بنسبة منيحة من اموال محفظته بنوع عقار معين (ارض ،شقق ،..) كمان بيعطيه اشعار بارتفاع مستوى الخطورة وانو ممكن يصير في ركود ع نوع هاد العقار فلازم ينوع .

    $property_type=$user->investment()->with('property_invested.property')->get()
        ->groupBy(fn($i)=>$i->property_invested->property->property_type ?? 'unknown')
        ->map(fn($group)=>$group->sum('amount_payed'));

    $total=$property_type->sum();
    $current_type=$property_for_sale->property_type;
    $typeShare = $total > 0 ? ($property_type[$current_type] ?? 0) / $total * 100 : 0;

     if($typeShare > 50) {

        $recommendations[]="نسبة استثمارك في نوع {$property_for_sale->property_type}مرتفعة،ننصح بالتنويع. ";
     }

//3
//ذا كان العقار العائد تبعو اقل من العائد يلي بيستثمر فيه المستخدم عادة (ممكن نعطيه خبر انو انت رح تستثمر بنسبة عائد اقل من المعتاد او انو نعرضلوا العقارات يلي بتناسب العائد يلي متعود عليه).


      $avg_return =$user->investment()->with('property_invested')->get()
      ->avg(fn($i)=>$i->property_invested->profit_percent);

     if($property_for_sale->profit_percent <$avg_return) {

         $recommendations[]="العائد المتوقع أقل من متوسط استثماراتك السابقة.";
     }

//4
 //ذا كان الوقت المحدد للاستثمار (investment time)ووقت الحصول على الارباح (incoming time)الفرق بينهم قليل يعني قصير اجل بيربح بسرعة ممكن نعطيه نصيحة يستثمر فيه .


    $investment_time = $property_for_sale->property_investment->investment_time ? strtotime($property_for_sale->property_investment->investment_time) : null;
    $incoming_time = $property_for_sale->property_investment->incoming_time ? strtotime($property_for_sale->property_investment->incoming_time) : null;

    if ($investment_time && $incoming_time) {
        $differentByDay = abs($incoming_time - $investment_time) / 86400;

        if ($differentByDay <= 30) {

            $recommendations[] = "هذا العقار يتيح لك أرباحاً سريعة خلال مدة قصيرة";
        }
    }



//5
    //اذا كان نمط العقار المعروض(investment mode) نفسو يلي بيستثمر فيه عادة منقلو انو هاد نوع المفضل او يعني منعرضو مع نصيحة وهيك.

      $preferredInvestmentMode=$user->investment()->with('property_invested')->get()
      ->pluck('property_invested.investment_mode')
      ->unique();

     if($preferredInvestmentMode->contains($property_for_sale->property_investment->investment_mode)){

         $recommendations[]="هذا العقار يتوافق مع نمط استثمارك المفضل.";
     }



//6
    //اذا كان عمر العقار المعروض( property age) نفسو يلي بيستثمر فيه عادة منقلو انو هاد عمر قريب عالعمر يلي بستثمر فيه عادة.

    $avg_age=$user->investment()->with('property_invested.property')->get()
        ->avg(fn($i)=>$i->property_invested->property->property_age ?? 0);

       if($property_for_sale->property_age && abs($property_for_sale->property_age - $avg_age) <=2){

           $recommendations[]="عمر هذا العقار مشابه لعقاراتك السابقة.";
       }

        return $recommendations;
   }

}
