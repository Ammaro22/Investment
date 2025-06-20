<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardTransactions extends Model
{
    use HasFactory;
    protected $table ='reward_transactions';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'reward_id',
        'amount_profit',
        'state',
        'number_of_times',
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function reward()
    {
        return $this->belongsTo(Reward::class,'reward_id');
    }
}
