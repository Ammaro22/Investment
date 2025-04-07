<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:4|max:255|',
            'password' => 'required|string|min:6|confirmed',
            'email' => 'required|string|email|unique:users,email|max:255',
            'phone'=>'required|string|max:255',
            'role_id'=>'required|exists:roles,id',
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

        return response([
            'message' => trans('messages.login_success'),
            'data' => $user,
            'token' => $token,
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

//    public function update(Request $request)
//    {
//        $user = auth()->user();
//
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|string|min:4|max:255|',
//            'password' => 'required|string|min:6',
//            'email' => 'required|string|email|unique:users,email|max:255',
//            'phone'=>'required|string|max:255',
//            'role_id'=>'required|exists:roles,id',
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['errors' => $validator->errors()], 400);
//        }
//
//        $user->update([
//            'name' => $request->input('name', $user->name),
//            'password' => $request->input('password') ? bcrypt($request->input('password')) : $user->password,
//            'email' => $request->input('email', $user->email),
//            'phone' => $request->input('phone', $user->phone),
//            'role_id' => $request->input('role_id', $user->role_id),
//        ]);
//
//        return response()->json([
//            'message' => trans('messages.update_success'),
//            'user' => $user,
//        ]);
//    }
    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|min:4|max:255',
            'password' => 'nullable|string|min:6',
            'email' => 'nullable|string|email|unique:users,email,'.$user->id.'|max:255',
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

}
