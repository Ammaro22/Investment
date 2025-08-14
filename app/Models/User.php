<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'users';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'verification_code',
        'role_id',
        'fcm_token',
        'personal_photo',
        'active'
    ];
    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }


    public function wallets()
    {
        return $this->hasMany(Wallet::class,'user_id');
    }
    public function help()
    {
        return $this->hasMany(Help::class,'user_id');
    }

    public function investmentcertificates()
    {
        return $this->hasMany(InvestmentCertificate::class,'user_id');
    }

    public function propertySale(){
        return $this->hasmany(Property_for_sale::class,'user_id');
    }

    public function deputization(){
        return $this->hasOne(Deputization::class,'user_id');
    }

    public function investment()
    {
        return $this->hasMany(Investment::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'user_id');
    }

    public function AmountInvested()
    {
        return $this->hasOne(AmountInvested::class, 'user_id');
    }

    public function AutomaticInvestment ()
    {
        return $this->hasOne(AutomaticInvestment::class, 'user_id');
    }


    public function RewardTransactions()
    {
        return $this->hasMany(RewardTransactions::class, 'user_id');
    }

    public function EmployeeInformation()
    {
        return $this->hasOne(EmployeeInformation::class, 'user_id');
    }

    public function RequestForOwnership()
    {
        return $this->hasMany(RequestForOwnership::class, 'user_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
