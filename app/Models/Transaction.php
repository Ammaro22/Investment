<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = ['user_id', 'wallet_id', 'amount', 'type', 'status', 'stripe_payment_id', 'related_transaction_id'];

    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
}
