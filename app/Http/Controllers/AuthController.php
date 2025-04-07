<?php
namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function sendVerificationCode(Request $request)
    {

        $validatedData = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ])->validate();

        $verificationCode = rand(100000, 999999);

        $user = User::where('email', $validatedData['email'])->first();
        $user->verification_code = $verificationCode;
        $user->save();

        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

        return response()->json([   'message' => trans('messages.operation_success')]);
    }

    public function verifyCode(Request $request)
    {

        $validatedData = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
            'verification_code' => 'required',
        ])->validate();

        $user = User::where('email', $validatedData['email'])->first();

        if ($user->verification_code == $validatedData['verification_code']) {
            $user->verification_code = null;
            $user->save();
            return response()->json([   'message' => trans('messages.operation_success')]);
        }

        return response()->json([   'message' => trans('messages.operation_failed')], 400);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->verification_code = null;
        $user->save();

        return response([   'message' => trans('messages.operation_success'),]);
    }

}
