<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class request_from_lawyer extends Model
{
    use HasFactory;
    protected $table='request_from_lawyers';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'property_for_sale_id',
        'status'
    ];
    public function property_for_sale()
    {
        return$this->belongsTo(Property_for_sale::class,'property_for_sale_id');
    }
}
