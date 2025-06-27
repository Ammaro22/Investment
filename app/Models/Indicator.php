<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    use HasFactory;

    protected $table='indicators';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'name',
        'arabic_name',
        'recommended_min',
        'recommended_max'
    ];






    public function values()
    {
        return $this->hasMany(IndicatorValue::class);
    }
}
