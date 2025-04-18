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
            'total_chance',
            'expected_price',
            'return_rate',
            'chance_price',
            'deadline_investment',
            'investment_type',
            'progress_percent',
            'is_completed'
    ];


    public function property(){

        return $this->belongsTo(Property_for_sale::class);
    }

    public function investment()
    {
        return $this->hasMany(Investment::class);

    }
}
