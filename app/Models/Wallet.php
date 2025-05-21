<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $table='wallets';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'wallet_type',
        'is_active'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }
}
