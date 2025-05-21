<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripePayment extends Model
{
    use HasFactory;
    protected $table='stripe_payments';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'transaction_id',
        'payment_intent_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'receipt_url'
    ];



}
