<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Common\CountryController;
use App\Http\Controllers\Api\V1\Common\CarMakeAndModelController;
use App\Http\Controllers\Api\V1\Common\GoodsTypesController;
use App\Http\Controllers\Api\V1\Common\CancellationReasonsController;
use App\Http\Controllers\Api\V1\Common\FaqController;
use App\Http\Controllers\Api\V1\Common\SosController;
use App\Http\Controllers\Api\V1\Common\SupportTicketController;
use App\Http\Controllers\Api\V1\Common\PreferenceController;
use App\Http\Controllers\Api\V1\Common\ReferralController;
use App\Http\Controllers\Api\V1\Common\LandingQuickLinkController;

use App\Http\Controllers\Api\V1\VehicleType\VehicleTypeController;

use App\Http\Controllers\Api\V1\Notification\ShowNotificationController;
use App\Http\Controllers\Api\V1\PromotionTemplateController;


/*
|--------------------------------------------------------------------------
| Common Routes
|--------------------------------------------------------------------------
*/

Route::get('countries', [CountryController::class, 'index']);
Route::get('on-boarding', [CountryController::class, 'onBoarding']);
Route::get('on-boarding-driver', [CountryController::class, 'onBoardingDriver']);
Route::get('on-boarding-owner', [CountryController::class, 'onBoardingOwner']);


Route::prefix('common')->group(function () {

    Route::get('modules', [CarMakeAndModelController::class, 'getAppModule']);
    Route::get('test-api', [CarMakeAndModelController::class, 'testApi']);
    Route::get('ride_modules', [CarMakeAndModelController::class, 'mobileAppMenu']);

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

        // Goods Types
        Route::get('goods-types', [GoodsTypesController::class, 'index']);

        // Cancellation Reasons
        Route::get('cancallation/reasons', [CancellationReasonsController::class, 'index']);

        // FAQ
        Route::get('faq/list/{lat}/{lng}', [FaqController::class, 'index']);

        // SOS
        Route::get('sos/list/{lat}/{lng}', [SosController::class, 'index']);
        Route::post('sos/store', [SosController::class, 'store']);
        Route::post('sos/delete/{sos}', [SosController::class, 'delete']);

        // Support Tickets
        Route::get('ticket-titles', [SupportTicketController::class, 'index']);
        Route::post('make-ticket', [SupportTicketController::class, 'makeTicket']);
        Route::post('reply-message/{supportTicket}', [SupportTicketController::class, 'replyMessage']);
        Route::get('view-ticket/{supportTicket}', [SupportTicketController::class, 'viewTicketDetails']);
        Route::get('list', [SupportTicketController::class, 'tikcetList']);

        // Preferences
        Route::get('preferences', [PreferenceController::class, 'index']);
        Route::post('preferences/store', [PreferenceController::class, 'update']);

        // Referral
        Route::prefix('referral')->group(function () {
            Route::get('progress', [ReferralController::class, 'progress']);
            Route::get('history', [ReferralController::class, 'history']);
            Route::get('referral-condition', [ReferralController::class, 'referralCondition']);
            Route::get('driver-referral-condition', [ReferralController::class, 'driverReferralCondition']);
        });
    });

    // Public Quick Links
    Route::get('/mobile/privacy', [LandingQuickLinkController::class, 'showPrivacyPage']);
    Route::get('/mobile/terms', [LandingQuickLinkController::class, 'showTermsPage']);
});


/*
|--------------------------------------------------------------------------
| Vehicle Type Routes
|--------------------------------------------------------------------------
*/

Route::prefix('types')->group(function () {
    Route::get('/{service_location}', [VehicleTypeController::class, 'getVehicleTypesByServiceLocation']);
    Route::get('/sub-vehicle/{service_location}', [VehicleTypeController::class, 'getSubVehicleTypesByServiceLocation']);
});


/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
*/

Route::prefix('notifications')
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {

        Route::get('get-notification', [ShowNotificationController::class, 'getNotifications']);
        Route::any('delete-notification/{notification}', [ShowNotificationController::class, 'deleteNotification']);
        Route::any('delete-all-notification', [ShowNotificationController::class, 'deleteAllNotification']);
    });

        // promotion
        Route::get('/promotions/popup', [PromotionTemplateController::class, 'index']);  
