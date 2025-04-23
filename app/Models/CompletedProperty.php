<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletedProperty extends Model
{
    use HasFactory;


    protected $table='completed_property';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'property_for_investment_id',
        'property_management'
    ];


    public function property()
    {
        return $this->belongsTo(PropertyForInvestment::class,'property_for_investment_id');
    }

    public function profit()
    {
        return $this->hasOne(Profit::class, 'completed_property_id');
    }

}
