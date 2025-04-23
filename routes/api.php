<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FrequentlyQuestionsController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyController;
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
    Route::post('/admin/approve_property/{evaluation_id}',[InvestmentController::class,'approve_property']);
    Route::get('/wallets/ShowInvestmentWallet',[WalletController::class,'ShowInvestmentWallet'])->middleware('throttle:5,1');
    Route::get('/wallets/ShowProfitWallet',[WalletController::class,'ShowProfitWallet'])->middleware('throttle:5,1');
    Route::get('/wallets/ShowPlatformWallet',[WalletController::class,'ShowPlatformWallet'])->middleware('throttle:5,1');
    Route::get('/showPropertyInvestedByUser',[InvestmentController::class,'showPropertyInvestedByUser'])->middleware('throttle:5,1');
    Route::post('/invest',[InvestmentController::class,'invest'])->middleware('throttle:5,1');



});


/*تغير كلمة المرور*/
Route::post('send_verification_code', [AuthController::class, 'sendVerificationCode']);
Route::post('verify_code', [AuthController::class, 'verifyCode']);
Route::post('reset_password', [AuthController::class, 'resetPassword']);

//Route::post('/wallets/requestOtpForConfirmTransform',[WalletController::class,'requestOtpForConfirmTransform'])->middleware('throttle:5,1');

/*اضافة بيت للبيع من قبل المستخدم*/


Route::get('/get_properties_by_id/{property_for_sale_id}', [PropertyController::class, 'getPropertyById']);
Route::get('/get_image_for_property_by_id/{property_for_sale_id}', [PropertyController::class, 'getImageForPropertyById']);
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/create_properties', [PropertyController::class, 'store']);
    Route::post('/update_properties_by_admin/{property_for_sale_id}', [PropertyController::class, 'updatebyadmin']);
    Route::post('/update_properties_by_user/{property_for_sale_id}', [PropertyController::class, 'update']);
    Route::delete('/delete_properties/{property_for_sale_id}', [PropertyController::class, 'destroy']);
    Route::get('/get_my_properties', [PropertyController::class, 'getPropertiesByToken']);
});

/*مراجعة طلبات بيع العقارات*/
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/accept_Request/{requests_id}', [RequestController::class, 'acceptRequest']);
    Route::post('/reject_Request/{requests_id}', [RequestController::class, 'rejectRequest']);
    Route::get('/get_all_Request', [RequestController::class, 'getAllRequests']);
    Route::get('/get_Request_for_user', [RequestController::class, 'getMyRequests']);
});

/*اسئلة المساعدة*/
Route::delete('delete_question/{help_id}',[HelpController::class,'deleteQuestion']);
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/create_question_by_user', [HelpController::class, 'addHelp']);
    Route::post('/create_Answer_by_admin/{help_id}', [HelpController::class, 'addAnswer']);
    Route::get('/get_all_help_for_admin', [HelpController::class, 'getQuestions']);
    Route::get('/get_myQuestion', [HelpController::class, 'getMyQuestions']);
});

Route::get('/get_FrequentlyQuestions', [FrequentlyQuestionsController::class, 'index']);
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/create_FrequentlyQuestions', [FrequentlyQuestionsController::class, 'create']);
    Route::delete('delete_FrequentlyQuestions/{Frequently_Questions_id}',[FrequentlyQuestionsController::class,'destroy']);
});


/*عملية الاستثمار والعمليات على المحافظ*/

Route::group(["middleware"=>["auth:api"]],function (){

    Route::post('/stripe/ChargeInvestmentWallet',[StripeController::class,'ChargeInvestmentWallet'])->middleware('throttle:5,1');
    Route::post('/admin/approve_property/{evaluation_id}',[InvestmentController::class,'approve_property']);
    Route::get('/wallets/ShowInvestmentWallet',[WalletController::class,'ShowInvestmentWallet']);
    Route::get('/wallets/ShowProfitWallet',[WalletController::class,'ShowProfitWallet']);
    Route::get('/wallets/ShowPlatformWallet',[WalletController::class,'ShowPlatformWallet']);
    Route::post('/invest',[InvestmentController::class,'invest'])->middleware('throttle:5,1');
    Route::get('/showPropertyInvestedByUser',[InvestmentController::class,'showPropertyInvestedByUser']);
    Route::get('/ShowListOfUserInvestment',[InvestmentController::class,'ShowListOfUserInvestment']);
    Route::get('/ShowPercentageOfInvestments',[InvestmentController::class,'ShowPercentageOfInvestments']);





});



/*عرض العقارات للاستثمار*/
Route::get('/ShowProperty',[InvestmentController::class,'ShowProperty']);
Route::post('/ShowPropertyByType',[InvestmentController::class,'ShowPropertyByType']);
Route::post('/ShowPropertyByInvestmentType',[InvestmentController::class,'ShowPropertyByInvestmentType']);
Route::post('/ShowPropertyById/{property_id}',[InvestmentController::class,'ShowPropertyById']);



/*الفريق الاقتصادي*/
Route::group(["middleware"=>["auth:api"]],function (){

    Route::post('/updatePropertyManagementStatus',[InvestmentController::class,'updatePropertyManagementStatus'])->middleware('throttle:5,1');
    Route::get('/getCompletedProperty',[InvestmentController::class,'getCompletedProperty'])->middleware('throttle:5,1');


});

