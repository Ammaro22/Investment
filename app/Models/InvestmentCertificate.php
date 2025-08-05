<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentCertificate extends Model
{
    use HasFactory;

    protected $table='investment_certificates';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'investment_id',
        'user_id',
        'property_Location',
        'number_chance'
    ];

    public function Investment()
    {
        return $this->belongsTo(Investment::class,'investment_id');

    }
    public function user()
    {
        return $this->belongsTo(user::class,'user_id');

    }
}
