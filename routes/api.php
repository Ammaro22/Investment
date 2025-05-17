<?php

use App\Http\Controllers\AgreedNegotiationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ElectronicPropertyCertificateController;
use App\Http\Controllers\EmployeeInformationController;
use App\Http\Controllers\FrequentlyQuestionsController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\ReqeustFromAdminController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestFromExpertController;
use App\Http\Controllers\RequestFromLawyerController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Models\Request_from_admin;
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
    Route::post('/storeFcmToken',[UserController::class,'storeFcmToken']);
    Route::post('/showNotificationByType',[UserController::class,'showNotificationByType']);







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
    Route::get('/get_all_Request', [RequestController::class, 'getSeparatedRequests']);
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

/*ارسال طلب منمحامي الى الفرق الاقصادي*/

Route::group(["middleware"=>["auth:api"]],function() {
    Route::get('/get_all_request_from_lawyer', [RequestFromLawyerController::class, 'getRequestWithUser']);
    Route::get('/get_propertyBy_request_from_lawyer/{request_from_lawyer_id}', [RequestFromLawyerController::class, 'getPropertyByRequestId']);
    Route::delete('delete_request_from_lawyer/{request_from_lawyer_id}',[RequestFromLawyerController::class,'deleteRequest']);
});

/*انشاء اتفاق بين المستخدم ولفريق الخبير*/

Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/create_Agreed_Negotiation', [AgreedNegotiationController::class, 'createAgreedNegotiation']);
    Route::post('/update_Agreed_Negotiation/{Agreed_Negotiation_id}', [AgreedNegotiationController::class, 'updateAgreedNegotiation']);
    Route::post('/accept_Agreed_Negotiation_by_user/{Agreed_Negotiation_id}',[AgreedNegotiationController::class,'acceptAgreedNegotiation']);
    Route::post('/reject_Agreed_Negotiation_by_user/{Agreed_Negotiation_id}',[AgreedNegotiationController::class,'rejectAgreedNegotiation']);
    Route::get('/get_Agreed_Negotiation_for_property/{property_for_sale_id}',[AgreedNegotiationController::class,'getAgreedNegotiationsByPropertyId']);
    Route::get('/get_Agreed_Negotiation_for_user',[AgreedNegotiationController::class,'getAgreedNegotiationsForUser']);
});

/*ارسال طلب مع تقرير من الفريق الخبير االى اللادمن*/

Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/create_request_from_expert', [RequestFromExpertController::class, 'createRequestFromExpert']);
    Route::post('/update_request_from_expert/{request_from_expert_id}', [RequestFromExpertController::class, 'updateEconomicEvaluation']);
    Route::get('/get_all_Rejected_requests', [RequestFromExpertController::class, 'getRejectedRequests']);
    Route::delete('delete_request_expert/{request_from_expert_id}',[RequestFromExpertController::class,'deleteRequest']);
});

/*تعامل مع ا لتقاري القادمة من الفرق الخبير من قبل الادمن*/
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/accept_request_from_expert/{request_from_expert_id}', [RequestFromExpertController::class, 'acceptRequest']);
    Route::post('/reject_request_from_expert/{request_from_expert_id}', [RequestFromExpertController::class, 'rejectRequest']);
    Route::get('/get_all_requests', [RequestFromExpertController::class, 'getAllRequestsForAdmin']);
    Route::get('/get_requests_by_id/{request_from_expert_id}',[RequestFromExpertController::class,'getRequestFromExpertById']);

});

/*ارسال تقير من الادمن الى المحامي لشراء العقار*/

Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/add_image_for_Document/{Request_from_admin_id}', [ReqeustFromAdminController::class, 'addImagesAndCompleteRequest']);
    Route::get('/get_buy_request_completed', [ReqeustFromAdminController::class, 'getCompletedRequests']);
    Route::get('/get_buy_request/{Request_from_admin_id}', [ReqeustFromAdminController::class, 'getRequestWithImages']);
    Route::get('/wallets/ShowPlatformWallet',[WalletController::class,'ShowPlatformWallet']);
    Route::get('/getLogsForUser/{user_id}',[UserController::class,'getLogsForUser']);

});

Route::group(["middleware"=>["auth:api"]],function() {

    Route::get('/get_all_Electronic_Property_Certificate_for_lawyer', [ElectronicPropertyCertificateController::class, 'showCertificates']);
    Route::get('/get_Electronic_Property_Certificate_for_user', [ElectronicPropertyCertificateController::class, 'showUserCertificates']);
});

/*عملية الاستثمار والعمليات على المحافظ*/

Route::group(["middleware"=>["auth:api"]],function (){

    Route::post('/stripe/ChargeInvestmentWallet',[StripeController::class,'ChargeInvestmentWallet'])->middleware('throttle:5,1');
    Route::get('/wallets/ShowInvestmentWallet',[WalletController::class,'ShowInvestmentWallet']);
    Route::get('/wallets/ShowProfitWallet',[WalletController::class,'ShowProfitWallet']);
    Route::post('/invest',[InvestmentController::class,'invest'])->middleware('throttle:5,1');
    Route::get('/showPropertyInvestedByUser',[InvestmentController::class,'showPropertyInvestedByUser']);
    Route::get('/ShowListOfUserInvestment',[InvestmentController::class,'ShowListOfUserInvestment']);
    Route::get('/ShowPercentageOfInvestments',[InvestmentController::class,'ShowPercentageOfInvestments']);
    Route::post('/ShowListOfUserInvestmentByInvestMode',[InvestmentController::class,'ShowListOfUserInvestmentByInvestMode']);
    Route::get('/ShowListOfUserProfit',[InvestmentController::class,'ShowListOfUserProfit']);
    Route::post('/ShowListOfUserProfitByInvestMode',[InvestmentController::class,'ShowListOfUserProfitByInvestMode']);




});

/*عرض العقارات للاستثمار*/
Route::get('/ShowProperty',[InvestmentController::class,'ShowProperty']);
Route::post('/ShowPropertyByType',[InvestmentController::class,'ShowPropertyByType']);
Route::post('/ShowPropertyByInvestmentType',[InvestmentController::class,'ShowPropertyByInvestmentType']);
Route::post('/ShowPropertyById/{property_id}',[InvestmentController::class,'ShowPropertyById']);



/*الادمن */
Route::group(["middleware"=>["auth:api"]],function (){
    Route::post('/admin/approve_property/{evaluation_id}',[InvestmentController::class,'approve_property']);
});

Route::group(["middleware"=>["auth:api"]],function (){
    Route::post('/add_info_employee/{user_Id}', [EmployeeInformationController::class, 'addEmployeeInformation']);
    Route::post('/search_user_by_role_and_name', [EmployeeInformationController::class, 'searchUsers']);
    Route::get('/get_info_users_by_id/{userId}', [EmployeeInformationController::class, 'getUserWithEmployeeInfo']);
    Route::post('/get_employee_by_role_and_active', [EmployeeInformationController::class, 'getUsersByRoleAndActive']);
    Route::post('/deactivate/{userId}', [EmployeeInformationController::class, 'deactivateUser']);
    Route::post('/activate/{userId}', [EmployeeInformationController::class, 'activateUser']);
});

/*الجوائز*/
Route::get('/get_Rewards',[RewardController::class,'getRewards']);
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/add_reward_by_admin', [RewardController::class, 'addReward']);
    Route::post('/update_reward_by_admin/{reward_id}', [RewardController::class, 'updateReward']);
    Route::delete('/delete_reward_by_admin/{reward_id}', [RewardController::class, 'deleteReward']);
    /*عرض الجوائز لليوزر*/
    Route::get('/get_User_Investments_And_Rewards',[RewardController::class,'getUserInvestmentsAndRewards']);
});


/*اضافة المؤشرات من قبل الفريق الخبير واضافة قيم لها*/
Route::group(["middleware"=>["auth:api"]],function() {
    Route::post('/storeIndicator',[IndicatorController::class,'storeIndicator']);
    Route::post('/storeValueToIndicator',[IndicatorController::class,'storeValueToIndicator']);
    Route::delete('/deleteIndicator/{indicator_id}', [IndicatorController::class, 'deleteIndicator']);
    Route::delete('/deleteValueOfIndicator/{indicatorValue_id}', [IndicatorController::class, 'deleteValueOfIndicator']);
    Route::post('/updateIndicator/{indicator_id}',[IndicatorController::class,'updateIndicator']);
    Route::post('/updateValueOfIndicator/{indicatorValue_id}',[IndicatorController::class,'updateValueOfIndicator']);



});
