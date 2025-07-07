<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorValue extends Model
{
    use HasFactory;
    protected $table='indicator_values';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'economic_evaluation_id',
        'property_id',
        'indicator_id',
        'value'
    ];




    public function evaluation()
    {
        return $this->belongsTo(EconomicEvaluation::class);
    }

    public function property()
    {
        return $this->belongsTo(PropertyForInvestment::class);
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }
}
