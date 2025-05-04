<?php

namespace App\Services;
use App\Models\User;
use DatabaseLogger;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{

    protected $messaging;
    protected $tokenProvider;

  public function __construct(FcmTokenProviderService $tokenProvider)
  {
      $this->messaging= (new Factory)
          ->withServiceAccount(config('firebase.credentials'))
          ->createMessaging();

      $this->tokenProvider= $tokenProvider;

  }

  public function sendToUser(User $user,string $title,string $body):void
  {
      if(!$user->fcm_token)return;

      try {
          $message=CloudMessage::withTarget('token',$user->fcm_token)
              ->withNotification(Notification::create($title,$body));

          $this->messaging->send($message);
          DatabaseLogger::log('info','FCM sendToUser success');


      }catch (\Throwable $e)
      {
          DatabaseLogger::log('error','FCM sendToUser failed:', (array)$e->getMessage());
      }

  }


  public function sendToRole(int $role_id,string $title,string $body):void
  {

      $tokens=$this->tokenProvider->getTokensByRole($role_id);

      if(empty($tokens))return;
      try {
          $message=CloudMessage::new()
              ->withNotification(Notification::create($title,$body));

          $this->messaging->sendMulticast($message,$tokens);

          DatabaseLogger::log('info','FCM sendToUser success');

      }catch (\Throwable $e) {

          DatabaseLogger::log('error','FCM sendToUser failed:', (array)$e->getMessage());
      }
      }

}
