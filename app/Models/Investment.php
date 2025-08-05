<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $table='investments';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'user_id',
        'chance_invested',
        'property_for_investment_id',
        'amount_payed',
    ];

    public function user()
    {
       return $this->belongsTo(User::class);

    }

    public function property_invested()
    {
        return $this->belongsTo(PropertyForInvestment::class,'property_for_investment_id');
    }

    public function investment_certificates()
    {
        return $this->hasMany(InvestmentCertificate::class,'investment_id');
    }

//    public function property_for_sale()
//    {
//        return $this->hasOneThrough(Property_for_sale::class,PropertyForInvestment::class,'id','id','property_for_investment_id','property_id');
//    }
}

