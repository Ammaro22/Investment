<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frequently_question extends Model
{
    use HasFactory;
    protected $table ='frequently_questions';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable =[
        'question',
        'Answer',
    ];
}
