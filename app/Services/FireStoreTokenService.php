<?php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
class FireStoreTokenService{


    protected $auth;
    public function __construct()
    {

        $this->auth=(new Factory())
            ->withServiceAccount(config('firebase.credentials'))->createAuth();

    }

    public function createCustomToken($uid)
    {
        return $this->auth->createCustomToken($uid)->toString();
    }


}
