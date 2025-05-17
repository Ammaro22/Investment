<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property_for_sale extends Model
{
    use HasFactory;
    protected $table = 'property_for_sales';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'property_type',
        'area',
        'number_of_rooms',
        'number_of_bathrooms',
        'property_age',
        'decoration',
        'kitchen_type',
        'flooring_type',
        'overlook_from',
        'balcony_size',
        'painting_type',
        'price',
        'pay_way',
        'state',
        'exact_position',
        'contract',
        'legal_check',
        'expert_check',
        'accept',
    ];

    public function Property_image(){
        return $this->hasmany(Property_image::class,'property_for_sale_id');
    }
    public function Property_document(){
        return $this->hasmany(Property_document::class,'property_for_sale_id');
    }
    public function id_image(){
        return $this->hasmany(id_image::class,'property_for_sale_id');
    }
    public function requests(){
        return $this->hasmany(Requests::class,'property_for_sale_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function property_investment()
    {
        return $this->hasOne(PropertyForInvestment::class,'property_id');
    }

    public function economicEvaluation()
    {
        return $this->hasOne(EconomicEvaluation::class,'property_for_sale_id');
    }
    public function request_from_lawyer()
    {
        return $this->hasOne(request_from_lawyer::class,'property_for_sale_id');
    }
    public function Agreed_negotiao()
    {
        return $this->hasOne(Agreed_negotiation::class, 'property_for_sale_id');
    }

    public function Request_from_admin()
    {
        return $this->hasOne(Request_from_admin::class, 'property_for_sale_id');
    }
}
