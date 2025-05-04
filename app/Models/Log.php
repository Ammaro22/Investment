<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id', 'level','message','context','channel',
        'record_datetime','remote_addr','user_agent'
    ];


    protected $casts = [
        'context' => 'array',
        'record_datetime' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }



}

