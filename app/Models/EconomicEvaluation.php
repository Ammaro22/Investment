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
        'total_chance',
        'expected_price',
        'expected_return',
        'evaluation_note',
        'status'
    ];



    public function property()
    {
        return$this->belongsTo(Property_for_sale::class,'property_id');
    }


}
