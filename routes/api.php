<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('signup',[UserController::class,'signup']);
Route::post('login', [UserController::class, 'login']);
Route::delete('delete_user/{user_id}',[UserController::class,'destroy']);
Route::group(["middleware"=>["auth:api"]],function (){
    Route::post('logout',[UserController::class,'logout']);
    Route::get('profile',[UserController::class,'profile']);
    Route::post('update',[UserController::class,'update']);
    Route::post('/stripe/ChargeInvestmentWallet',[StripeController::class,'ChargeInvestmentWallet'])->middleware('throttle:5,1');
    Route::post('/wallets/transferToPlatform',[WalletController::class,'transferToPlatform'])->middleware('throttle:5,1');

});
/*تغير كلمة المرور*/
Route::post('send_verification_code', [AuthController::class, 'sendVerificationCode']);
Route::post('verify_code', [AuthController::class, 'verifyCode']);
Route::post('reset_password', [AuthController::class, 'resetPassword']);



//Route::post('/wallets/requestOtpForConfirmTransform',[WalletController::class,'requestOtpForConfirmTransform'])->middleware('throttle:5,1');
