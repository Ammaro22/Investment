<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticInvestment extends Model
{
    use HasFactory;
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'start_date',
        'next_investment_date',
        'investment_mode',
        'active',
        'investment_amount',
        'expected_profit_min',
        'expected_profit_max',
        'min_chance_invested',
        'max_chance_invested',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
