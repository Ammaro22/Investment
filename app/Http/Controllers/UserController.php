<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FirebaseNotificationService;
use App\Services\FireStoreTokenService;
use DatabaseLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $fireStoreToken;
    protected $firebaseNotification;

    public function __construct(FirebaseNotificationService $firebaseNotification,FireStoreTokenService $fireStoreToken)
    {
        $this->firebaseNotification=$firebaseNotification;
        $this->fireStoreToken=$fireStoreToken;

    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|',
            'password' => 'required|string|min:6|confirmed',
            'email' => 'required|string|email|unique:users,email|max:255',
            'phone' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
        ]);
        $this->createWallets($user, $request->role_id);

        $accessToken = $user->createToken('authToken')->accessToken;
        DatabaseLogger::log('info','user sign up',['user_id'=>$user->id,
            'user_name'=>$user->name]);
        return response([
            'message' => trans('messages.account_created'),
            'user' => $user,
            'access_token' => $accessToken,
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            return response(['errors' => trans('messages.login_failed')], 422);
        }

        $user = auth()->user();
        $token = $user->createToken('Personal Access Token')->accessToken;

        $uid='user_'.$user->id;
        $firebaseToken=$this->fireStoreToken->createCustomToken($uid);

        DatabaseLogger::log('info','user logged in',['user_id'=>$user->id,
            'user_name'=>$user->name]);

        $this->firebaseNotification->sendToUser($user,'login_success');
        return response([
            'message' => trans('messages.login_success'),
            'data' => $user,
            'token' => $token,
            'firebaseToken'=>$firebaseToken,
            'uid'=>$uid
        ]);
    }

    public function profile()
    {
        $user_data = auth()->user();
        return response()->json([
            "message" => trans('messages.profile_success'),
            "data" => $user_data,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => trans('messages.logout_success'),
        ]);
    }


    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|min:4|max:255',
            'password' => 'nullable|string|min:6',
            'email' => 'nullable|string|email|unique:users,email,' . $user->id . '|max:255',
            'phone' => 'nullable|string|max:255',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $data = $request->only(['name', 'password', 'email', 'phone', 'role_id']);
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update(array_filter($data));

        return response()->json([
            'message' => trans('messages.update_success'),
            'user' => $user,
        ]);
    }

    public function destroy($id)
    {
        $us = User::find($id);
        $us->delete();
        return response()->json([
            'message' => trans('messages.operation_success'),
        ]);
    }

    private function createWallets(User $user, $roleId)
    {
        if ($roleId == 1) {
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'currency' => 'USD',
                'wallet_type' => 'platform',
                'is_active' => true,
            ]);
        } elseif ($roleId == 2) {

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'currency' => 'USD',
                'wallet_type' => 'investment',
                'is_active' => true,
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'currency' => 'USD',
                'wallet_type' => 'profits',
                'is_active' => true,
            ]);
        }
    }





    public function getLogsForUser($user_id)
    {
        if(!$user_id)
        {
            return response()->json(['message'=>'messages.not_found']);
        }

        $user=auth()->user();
        $userRole=$user->role_id;
        if(!$user||$userRole!=1)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }

        $logs=\App\Models\Log::where('user_id',$user_id)->paginate(5);

        $pagination = $logs->getCollection()->map(function ($paginate) {
            $context = json_decode($paginate->context, true);
            return [
                'id' => $paginate->id,
                'user_id' => $paginate->user_id,
                'message' => $paginate->message,
                'level' => $paginate->level,
                'record_datetime' => $paginate->record_datetime->format('Y-m-d'),
                'context' => $context,
            ];
        });


        return response()->json(['message'=>trans('messages.operation_success'),
            'data'=>[
                'logs'=>$pagination,
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'next_page_url' => $logs->nextPageUrl(),
                    'prev_page_url' => $logs->previousPageUrl(),
                ]
        ]]);
    }


    public function storeFcmToken(Request $request)
    {
        $user=auth()->user();

        if(!$user)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }
        $validator=Validator::make($request->all(),[
            'fcm_token'=>'required|string'
        ]);

        if($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()],400);
        }

        $user->update(['fcm_token'=>$request->fcm_token]);

        return response()->json(['message'=>trans('messages.operation_success')]);
    }



    public function showNotificationByType(Request $request)
    {
        $user=auth()->user();
        if(!$user)
        {
            return response()->json(['message'=>trans('messages.unauthorized')]);
        }

        $validator=Validator::make($request->all(), [
            'type'=>'required|string'

        ]);
        if($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()],400);
        }

        $notifications=Notification::where('user_id',$user->id)
            ->where('type',$request->type)
            ->get()->map(function ($notification)
        {
            return[
                'id'=>$notification->id,
                'user_id'=>$notification->user_id,
                'type'=>$notification->type,
                'title'=>$notification->title,
                'body'=>$notification->body,
                'created_at'=>$notification->created_at_formatted,
                'updated_at'=>$notification->updated_at_formatted
            ];
        });

        return response()->json(['message'=>trans('messages.operation_success'),'data'=>$notifications]);


    }
}
