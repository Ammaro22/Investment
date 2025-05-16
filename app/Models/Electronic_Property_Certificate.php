<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Electronic_Property_Certificate extends Model
{
    use HasFactory;
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'request_from_admin_id',
        'Seller_Name',
        'Property_Location',
        'lawyer_Name',
        'Payment_Method',
        'Property_Price',
    ];
    public function request_from_admin()
    {
        return$this->belongsTo(Request_from_admin::class,'request_from_admin_id');
    }
}
