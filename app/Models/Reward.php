<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;
    protected $table ='rewards';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'amount_threshold',
        'percentage',
        'level'
    ];

    public function RewardTransactions()
    {
        return $this->hasOne(RewardTransactions::class, 'reward_id');
    }

}
