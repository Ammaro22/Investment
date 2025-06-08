<?php

namespace App\Http\Controllers;


use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FirebaseNotificationService;
use App\Services\FireStoreTokenService;
use DatabaseLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    protected $firebaseNotification;
    protected $fireStoreTokenService;
    public function __construct(FirebaseNotificationService $firebaseNotification,FireStoreTokenService $fireStoreTokenService)
    {
        $this->firebaseNotification=$firebaseNotification;
        $this->fireStoreTokenService=$fireStoreTokenService;
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
        $firebaseToken=$this->fireStoreTokenService->createCustomToken($uid);

        DatabaseLogger::log('info','user logged in',['user_id'=>$user->id,
            'user_name'=>$user->name]);

        $this->firebaseNotification->sendToUser($user,'login_success');
        return response([
            'message' => trans('messages.login_success'),
            'data' => $user,
            'token' => $token,
            'firebase_token'=>$firebaseToken,
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

        DatabaseLogger::log('info','user logged out',['user_id'=>$request->user()->id,
            'user_name'=>$request->user()->name]);
        return response()->json([
            'message' => trans('messages.logout_success'),
        ]);
    }


//    public function update(Request $request)
//    {
//        $user = auth()->user();
//
//        $userRole=$user->role_id;
//
//        $validator = Validator::make($request->all(), [
//            'name' => 'nullable|string|min:4|max:255',
//            'password' => 'nullable|string|min:6',
//            'email' => 'nullable|string|email|unique:users,email,' . $user->id . '|max:255',
//            'phone' => 'nullable|string|max:255',
//            'role_id' => 'nullable|exists:roles,id',
//            'personal_photo' => 'nullable|image|mimes:jpg,jpeg,png'
//
//        ]);
//
//
//        if ($validator->fails()) {
//            return response()->json(['errors' => $validator->errors()], 400);
//        }
//        if($userRole==2) {
//            $allowed_fields=['name', 'password', 'email', 'phone', 'personal_photo'];
//
//        }if($userRole==3||$userRole==4){
//            $allowed_fields=['name', 'password', 'phone', 'personal_photo'];
//        }
//
//
//        $sentFields = array_keys($request->all());
//        $invalidFields = array_diff($sentFields, $allowed_fields);
//
//        if (!empty($invalidFields)) {
//            return response()->json([
//                'message' => 'غير مخول لتعديل الحقول التالية:',
//                'fields' => array_values($invalidFields)
//            ], 403);
//        }
//
//        $data=$request->only($allowed_fields);
//
//        if (isset($data['password'])) {
//            $data['password'] = bcrypt($data['password']);
//        }
//
//        if ($request->hasFile('personal_photo')) {
//            if ($user->personal_photo && file_exists(public_path($user->personal_photo))) {
//                unlink(public_path($user->personal_photo));
//            }
//
//            $personalPhoto = $request->file('personal_photo');
//            $personalPhotoName = time() . '_personal_' . $personalPhoto->getClientOriginalName();
//            $personalPhoto->move(public_path('images/personal'), $personalPhotoName);
//            $data['personal_photo'] = "images/personal/$personalPhotoName";
//        }
//
//        $user->update(array_filter($data));
//
//        return response()->json([
//            'message' => trans('messages.update_success'),
//            'user' => $user,
//        ]);
//    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->role_id;

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|min:4|max:255',
            'password' => 'nullable|string|min:6',
            'email' => 'nullable|string|email|unique:users,email,' . $user->id . '|max:255',
            'phone' => 'nullable|string|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'personal_photo' => 'nullable|image|mimes:jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Initialize allowed_fields with an empty array
        $allowed_fields = [];

        if ($userRole == 2 || $userRole == 1) {
            $allowed_fields = ['name', 'password', 'email', 'phone', 'personal_photo'];
        } elseif ($userRole == 3 || $userRole == 4) {
            $allowed_fields = ['name', 'password', 'phone', 'personal_photo'];
        }

        $sentFields = array_keys($request->all());
        $invalidFields = array_diff($sentFields, $allowed_fields);

        if (!empty($invalidFields)) {
            return response()->json([
                'message' => 'غير مخول لتعديل الحقول التالية:',
                'fields' => array_values($invalidFields)
            ], 403);
        }

        $data = $request->only($allowed_fields);

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        if ($request->hasFile('personal_photo')) {
            if ($user->personal_photo && file_exists(public_path($user->personal_photo))) {
                unlink(public_path($user->personal_photo));
            }

            $personalPhoto = $request->file('personal_photo');
            $personalPhotoName = time() . '_personal_' . $personalPhoto->getClientOriginalName();
            $personalPhoto->move(public_path('images/personal'), $personalPhotoName);
            $data['personal_photo'] = "images/personal/$personalPhotoName";
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
            return response()->json(['message'=>trans('messages.not_found')]);
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







}
