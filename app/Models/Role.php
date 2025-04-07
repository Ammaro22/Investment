<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $table = 'roles';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'type',
    ];

    public function item(){
        return $this->hasmany(User::class,'role_id');
    }

}
