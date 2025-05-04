<?php

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class DatabaseLogger
{

  public static function log($level,$message,array$context=[],$channel='database')
  {


      Log::create([

          'user_id' => Auth::id() ?? ($context['user_id'] ?? null),
          'level'=>$level,
          'message'=>$message,
          'context'=>json_encode($context),
          'channel'=>$channel,
          'remote_addr'=>Request::ip(),
          'user_agent'=>Request::header('User_Agent'),
          'record_datetime'=>now()

      ]);

  }

}
