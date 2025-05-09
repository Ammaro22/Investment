<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInformation extends Model
{
    use HasFactory;
    protected $table ='employee_information';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'current_address',
        'front_id_image',
        'back_id_image',
        'date_of_birth'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
