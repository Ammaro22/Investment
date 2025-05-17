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

  public function sendToUser(User $user,string $type):void
  {
      if(!$user->fcm_token)return;

      $template=$this->getNotificationByType($type,app()->getLocale());

      try {
          if($template){
          $message=CloudMessage::withTarget('token',$user->fcm_token)
              ->withNotification(Notification::create($template['title'],$template['body']));

          $this->messaging->send($message);


          $this->storeNotification($user->id,$template['title'],$template['body'],$type);
          }
      }catch (\Throwable $e)
      {
          DatabaseLogger::log('error','FCM sendToUser failed:', (array)$e->getMessage());
      }


  }

  public function storeNotification($user_id,$title,$body,$type)
  {
   if(!$user_id)
   {
       return response()->json(['message'=>'messages.not_found']);
   }
     $notification= \App\Models\Notification::create([
         'user_id'=>$user_id,
         'type'=>$type,
         'title'=>$title,
         'body'=>$body,
         'created_at'=>now()->format('Y-m-d'),
         'updated_at'=>now()->format('Y-m-d')

      ]);

   return response()->json(['message'=>trans('messages.operation_success')]);


  }

  public function getNotificationByType($type,$lang='ar')
  {
      if(!$type)
      {
          return response()->json(['message'=>trans('messages.not_found')]);
      }
      $ar_path=resource_path("lang/{$lang}/notification.php");

      if(!file_exists($ar_path))
      {
          $en_path=resource_path("lang/en/notification.php");
          if(!file_exists($en_path))
          {
              return null;
          }
          $template=include($en_path);

      }else {
          $template = include($ar_path);
      }
      return $template[$type]??null;
  }


  public function sendToRole(int $role_id,string $title,string $body):void
  {

      $tokens=$this->tokenProvider->getTokensByRole($role_id);

      if(empty($tokens))return;
      try {
          $message=CloudMessage::new()
              ->withNotification(Notification::create($title,$body));

          $this->messaging->sendMulticast($message,$tokens);


      }catch (\Throwable $e) {

          DatabaseLogger::log('error','FCM sendToUser failed:', (array)$e->getMessage());
      }
      }

}
