<?php

namespace App\Http\Controllers\v1;

use App\Services\FirebaseNotificationService;
use App\Services\FireStoreTokenService;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    protected $firebaseNotification;
    protected $fireStoreTokenService;
    public function __construct(FirebaseNotificationService $firebaseNotification,FireStoreTokenService $fireStoreTokenService)
    {
        $this->firebaseNotification=$firebaseNotification;
        $this->fireStoreTokenService=$fireStoreTokenService;

    }

}
