<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deputization extends Model
{
    use HasFactory;
    protected $table='deputizations';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'user_id',
        'ID_Number',
        'status',
        'deputization_Content',
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
