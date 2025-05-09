<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agreed_negotiation extends Model
{
    use HasFactory;

    protected $table='Agreed_negotiations';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'expert_id',
        'property_for_sale_id',
        'Text_of_the_agreement',
        'Payment_Mechanism',
        'status'
    ];

    public function profit()
    {
        return $this->hasOne(EconomicEvaluation::class, 'agreed_negotiations_id');
    }

    public function expert()
    {
        return $this->belongsTo(User::class,'expert_id');
    }

    public function propertySale()
    {
        return $this->belongsTo(Property_for_sale::class, 'property_for_sale_id');
    }

}
