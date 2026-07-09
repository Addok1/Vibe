<?php

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with 'api/v1'.
| These routes use the root namespace 'App\Http\Controllers\Api\V1'.
|
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Request\EtaController;
use App\Http\Controllers\Api\V1\Request\PromoCodeController;
use App\Http\Controllers\Api\V1\Request\CreateRequestController;
use App\Http\Controllers\Api\V1\Request\DeliveryCreateRequestController;
use App\Http\Controllers\Api\V1\Request\UserCancelRequestController;
use App\Http\Controllers\Api\V1\Request\InstantRideController;
use App\Http\Controllers\Api\V1\Request\RequestAcceptRejectController;
use App\Http\Controllers\Api\V1\Request\DriverArrivedController;
use App\Http\Controllers\Api\V1\Request\DriverTripStartedController;
use App\Http\Controllers\Api\V1\Request\DriverCancelRequestController;
use App\Http\Controllers\Api\V1\Request\DriverEndRequestController;
use App\Http\Controllers\Api\V1\Request\DriverDeliveryProofController;
use App\Http\Controllers\Api\V1\Request\RequestHistoryController;
use App\Http\Controllers\Api\V1\Request\RatingsController;
use App\Http\Controllers\Api\V1\Request\ChatController;

/*
 * These routes are prefixed with 'api/v1/request'.
 * These routes use the root namespace 'App\Http\Controllers\Api\V1\Request'.
 * These routes use the middleware group 'auth'.
 */
Route::prefix('request')
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | USER ROUTES
        |--------------------------------------------------------------------------
        */

        Route::post('list-packages', [EtaController::class, 'listPackages']);
        Route::get('promocode-list', [PromoCodeController::class, 'index']);
        Route::post('promocode-redeem', [PromoCodeController::class, 'redeem']);
        Route::post('promocode-clear', [PromoCodeController::class, 'clear']);

        Route::post('create', [CreateRequestController::class, 'createRequest']);
        Route::post('delivery/create', [DeliveryCreateRequestController::class, 'createRequest']);

        Route::post('change-drop-location', [EtaController::class, 'changeDropLocation']);
        Route::post('cancel', [UserCancelRequestController::class, 'cancelRequest']);
        Route::post('respond-for-bid', [CreateRequestController::class, 'respondForBid']);

        Route::post('user/payment-method', [UserCancelRequestController::class, 'paymentMethod']);
        Route::post('user/payment-confirm', [UserCancelRequestController::class, 'userPaymentConfirm']);
        Route::post('user/driver-tip', [UserCancelRequestController::class, 'driverTip']);

        // ETA
        Route::post('eta', [EtaController::class, 'eta']);
        Route::post('serviceVerify', [EtaController::class, 'serviceVerify']);
        Route::post('list-recent-searches', [EtaController::class, 'recentSearches']);
        Route::get('get-directions', [EtaController::class, 'getDirections']);


        /*
        |--------------------------------------------------------------------------
        | DRIVER ROUTES
        |--------------------------------------------------------------------------
        */

        Route::post('create-instant-ride', [InstantRideController::class, 'createRequest']);
        Route::post('create-delivery-instant-ride', [InstantRideController::class, 'createDeliveryRequest']);

        Route::post('respond', [RequestAcceptRejectController::class, 'respondRequest']);
        Route::post('arrived', [DriverArrivedController::class, 'arrivedRequest']);
        Route::post('started', [DriverTripStartedController::class, 'tripStart']);
        Route::post('cancel/by-driver', [DriverCancelRequestController::class, 'cancelRequest']);
        Route::post('end', [DriverEndRequestController::class, 'endRequest']);
        Route::post('trip-meter', [DriverEndRequestController::class, 'tripMeterRideUpdate']);
        Route::post('upload-proof', [DriverDeliveryProofController::class, 'uploadDocument']);

        Route::post('payment-confirm', [DriverEndRequestController::class, 'paymentConfirm']);
        Route::post('payment-method', [DriverEndRequestController::class, 'paymentMethod']);

        Route::post('ready-to-pickup', [DriverTripStartedController::class, 'readyToPickup']);
        Route::post('stop-complete', [DriverEndRequestController::class, 'tripEndBystop']);
        Route::post('stop-otp-verify', [DriverEndRequestController::class, 'stopOtpVerify']);
        Route::post('additional-charge', [DriverEndRequestController::class, 'additionalChargeUpdate']);


        /*
        |--------------------------------------------------------------------------
        | HISTORY & RATINGS
        |--------------------------------------------------------------------------
        */

        Route::get('history', [RequestHistoryController::class, 'index']);
        Route::get('history/{id}', [RequestHistoryController::class, 'getById']);
        Route::get('invoice/{requestmodel}', [RequestHistoryController::class, 'invoice']);

        Route::post('rating', [RatingsController::class, 'rateRequest']);


        /*
        |--------------------------------------------------------------------------
        | CHAT
        |--------------------------------------------------------------------------
        */

        Route::get('chat-history/{request}', [ChatController::class, 'history']);
        Route::post('send', [ChatController::class, 'send']);
        Route::post('seen', [ChatController::class, 'updateSeen']);

        Route::get('user-chat-history', [ChatController::class, 'initiateConversation']);
        Route::post('user-send-message', [ChatController::class, 'sendMessage']);
    });

