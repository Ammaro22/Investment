<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class request_from_expert extends Model
{
    use HasFactory;
    protected $table='request_from_experts';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable=[
        'request_from_lawyer_id',
        'economic_evaluation_id',
        'note_admin',
        'status'
    ];
    public function economic_evaluation()
    {
        return$this->belongsTo(EconomicEvaluation::class,'economic_evaluation_id');
    }
    public function Request_from_lower()
    {
        return$this->belongsTo(request_from_lawyer::class,'request_from_lawyer_id');
    }
    public function Request_from_admin()
    {
        return $this->hasOne(Request_from_admin::class, 'request_from_expert_id');
    }
}
