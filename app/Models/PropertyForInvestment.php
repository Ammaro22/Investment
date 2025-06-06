<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyForInvestment extends Model
{
    use HasFactory;

    protected $table='property_for_investment';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable= [
            'property_id',
            'number_of_chances',
            'expected_price',
            'profit_percent',
            'chance_price',
            'investment_time',
            'incoming_time',
            'investment_mode',
            'progress_percent',
            'property_management',
            'is_completed'
    ];


    public function property(){

        return $this->belongsTo(Property_for_sale::class,'property_id');
    }

    public function investment()
    {
        return $this->hasMany(Investment::class,'property_for_investment_id');

    }

    public function completedProperty()
    {
        return $this->hasMany(CompletedProperty::class,'property_for_investment_id');

    }
}
