<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request_from_admin extends Model
{
    use HasFactory;
    protected $table='request_from_admins';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'request_from_expert_id',
        'property_for_sale_id',
        'type_request',
        'status'
    ];

    public function proprtsseale()
    {
        return $this->belongsTo(Property_for_sale::class, 'property_for_sale_id');
    }

    public function request_from_expert()
    {
        return$this->belongsTo(request_from_expert::class,'request_from_expert_id');
    }
    public function Electronic_Property_Certificate()
    {
        return $this->hasOne(Electronic_Property_Certificate::class, 'request_from_admin_id');
    }
}
