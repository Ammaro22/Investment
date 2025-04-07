<?php

namespace App\Models;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory,Imageable;
    protected $table ='images';
    protected $fillable =[
        'name',
        'path',
        'item_id',

    ];
    public function sub(){
        return $this->belongsTo(Item::class);
    }
}
