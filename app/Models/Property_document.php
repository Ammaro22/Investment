<?php

namespace App\Models;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property_document extends Model
{
    use HasFactory,Imageable;
    protected $table ='property_documents';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable =[
        'name',
        'path',
        'property_for_sale_id',

    ];
    public function property_for_sale(){
        return $this->belongsTo(Property_for_sale::class,'property_for_sale_id');
    }
}
