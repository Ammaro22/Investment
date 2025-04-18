<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EconomicEvaluation extends Model
{
    use HasFactory;
    protected $table='economic_evaluations';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'property_id',
        'number_of_chances',
        'expected_price',
        'profit_percent',
        'total_expected_taxes',
        'baying_price',
        'chance_price',
        'investment_time',
        'incoming_time',
        'investment_mode',
        'property_management',
        'status'
    ];



    public function property()
    {
        return$this->belongsTo(Property_for_sale::class,'property_id');
    }


}
