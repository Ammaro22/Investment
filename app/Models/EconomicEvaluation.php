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
        'property_for_sale_id',
        'number_of_chances',
        'expected_price',
        'profit_percent',
        'total_expected_taxes',
        'buying_price',
        'renting_price',
        'chance_price',
        'investment_time',
        'incoming_time',
        'investment_mode',
        'property_management',
        'agreed_negotiations_id'
    ];



    public function property()
    {
        return$this->belongsTo(Property_for_sale::class,'property_for_sale_id');
    }
    public function agreed_negotiation()
    {
        return$this->belongsTo(Agreed_negotiation::class,'agreed_negotiations_id');
    }
    public function request_from_expert()
    {
        return $this->hasOne(request_from_expert::class, 'economic_evaluation_id');
    }

}
