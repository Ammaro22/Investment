<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmountInvested extends Model
{
    use HasFactory;
    protected $table ='amount_investeds';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'amount_invested'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
