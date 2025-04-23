<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profit extends Model
{
    use HasFactory;

    protected $table='profits';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'completed_property_id',
        'profit_amount',
        'user_id',
        'scheduled_date',
        'transfer_status',
        'processed_at',
        'transfer_attempts',
        'failure_reason'
    ];


    public function completedProperty()
    {
        return $this->belongsTo(CompletedProperty::class, 'completed_property_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
