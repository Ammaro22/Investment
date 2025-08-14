<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForOwnership extends Model
{
    use HasFactory;
    protected $table ='request_for_ownerships';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable =[
        'investment_certificate_id',
        'seller_id',
        'buyer_id',
        'Tax',
        'status'
    ];
    public function investment_certificates(){
        return $this->belongsTo(InvestmentCertificate::class,'investment_certificate_id');

    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
