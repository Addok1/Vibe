<?php

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with 'api/v1'.
| These routes use the root namespace 'App\Http\Controllers\Api\V1'.
|
 */
use App\Base\Constants\Auth\Role;

/**
 * These routes are prefixed with 'api/v1'.
 * These routes use the root namespace 'App\Http\Controllers\Api\V1\Driver'.
 * These routes use the middleware group 'auth'.
 */
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Driver\DriverDocumentController;
use App\Http\Controllers\Api\V1\Driver\OnlineOfflineController;
use App\Http\Controllers\Api\V1\Driver\EarningsController;
use App\Http\Controllers\Api\V1\Driver\SubscriptionController;
use App\Http\Controllers\Api\V1\Driver\IncentiveController;
use App\Http\Controllers\Api\V1\Driver\DriverLevelController;



Route::prefix('driver')
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {

        // Driver Documents
        Route::get('documents/needed', [DriverDocumentController::class, 'index']);
        Route::post('upload/documents', [DriverDocumentController::class, 'uploadDocuments']);
        Route::get('diagnostic', [DriverDocumentController::class, 'diagnostics']);

        // Online / Offline
        Route::post('online-offline', [OnlineOfflineController::class, 'toggle']);
        Route::post('add-my-route-address', [OnlineOfflineController::class, 'addMyRouteAddress']);
        Route::post('enable-my-route-booking', [OnlineOfflineController::class, 'enableMyRouteBooking']);

        // Earnings
        Route::get('today-earnings', [EarningsController::class, 'index']);
        Route::get('weekly-earnings', [EarningsController::class, 'weeklyEarnings']);
        Route::get('earnings-report/{from_date}/{to_date}', [EarningsController::class, 'earningsReport']);
        Route::get('history-report', [EarningsController::class, 'historyReport']);
        Route::post('update-price', [EarningsController::class, 'updatePrice']);
        Route::get('new-earnings', [EarningsController::class, 'newEarnings']);
        Route::post('earnings-by-date', [EarningsController::class, 'earningsByDate']);
        Route::get('all-earnings', [EarningsController::class, 'allEarnings']);
        Route::get('leader-board/trips', [EarningsController::class, 'leaderBoardTrips']);
        Route::get('leader-board/earnings', [EarningsController::class, 'leaderBoardEarnings']);
        Route::get('invoice-history', [EarningsController::class, 'invoiceHistory']);

        // Subscription
        Route::get('list_of_plans', [SubscriptionController::class, 'listOfSubscription']);
        Route::post('subscribe', [SubscriptionController::class, 'addSubscription']);

        // Incentives
        Route::get('new-incentives', [IncentiveController::class, 'newIncentive']);
        Route::get('week-incentives', [IncentiveController::class, 'weekIncentives']);

        // Bank Info
        Route::get('list/bankinfo', [DriverDocumentController::class, 'listBankInfo']);
        Route::post('update/bankinfo', [DriverDocumentController::class, 'updateBankinfoNew']);

        // Loyalty & Rewards
        Route::get('loyalty/history', [DriverLevelController::class, 'listLevel']);
        Route::get('rewards/history', [DriverLevelController::class, 'listRewards']);
    });