<?php

use App\Http\Controllers\TripRequestController;
use App\Http\Controllers\DeliveryRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //  trip ride  request
    Route::group(['prefix' => 'rides-request'], function () {
        Route::middleware(['permission:trip-request-view'])->get('/', [TripRequestController::class, 'ridesRequest'])->name('triprequest.ridesRequest');
        Route::middleware('remove_empty_query')->get('/list', [TripRequestController::class, 'list'])->name('triprequest.list');
        Route::post('/driver/{driver}', [TripRequestController::class, 'driverFind'])->name('triprequest.driverFind');
        Route::get('/view/{requestmodel}', [TripRequestController::class, 'viewDetails'])->name('triprequest.viewDetails');
        Route::get('/cancel/{requestmodel}', [TripRequestController::class, 'cancelRide'])->name('triprequest.cancel');
        Route::get('/detail/{request}', [TripRequestController::class, 'sosDetail'])->name('triprequest.sosDetail');
        Route::get('/download-invoice/{requestmodel}', [TripRequestController::class, 'downloadInvoice']);

        // send invoice mail
        Route::post('/send-invoice-mail/{requestmodel}', [TripRequestController::class, 'sendInvoicemail']);
    });

    //  delivery ride request
    Route::group(['prefix' => 'delivery-rides-request'], function () {
        Route::middleware(['permission:manage-delivery-request'])->get('/', [DeliveryRequestController::class, 'ridesRequest'])->name('deliveryTriprequest.ridesRequest');
        Route::middleware('remove_empty_query')->get('/list', [DeliveryRequestController::class, 'list'])->name('deliveryTriprequest.list');
        Route::post('/driver/{driver}', [DeliveryRequestController::class, 'driverFind'])->name('deliveryTriprequest.driverFind');
        Route::get('/view/{requestmodel}', [DeliveryRequestController::class, 'viewDetails'])->name('deliveryTriprequest.viewDetails');
        Route::get('/cancel/{requestmodel}', [DeliveryRequestController::class, 'cancelRide'])->name('deliveryTriprequest.cancel');
        Route::get('/download-invoice/{requestmodel}', [DeliveryRequestController::class, 'downloadInvoice']);
    });



    // ongoing rides
    Route::group(['prefix' => 'ongoing-rides'], function () {
        Route::middleware(['permission:ongoing-request-view'])->get('/', [TripRequestController::class, 'ongoingRidesRequest'])->name('triprequest.ongoingRides');
        Route::get('/find-ride/{request}', [TripRequestController::class, 'ongoingRideDetail'])->name('triprequest.ongoingRideDetail');
        Route::get('/assign/{request}', [TripRequestController::class, 'assignView'])->name('triprequest.assignView');
        Route::post('/assign-driver/{requestmodel}', [TripRequestController::class, 'assignDriver'])->name('triprequest.assignDriver');
    });


    // Track Request
    Route::get('/track/request/{request}', [TripRequestController::class, 'trackRequest'])->name('triprequest.trackRequest');
    Route::get('/download-user-invoice/{requestmodel}', [TripRequestController::class, 'downloadUserInvoice']);
    Route::get('/download-driver-invoice/{requestmodel}', [TripRequestController::class, 'downloadDriverInvoice']);
});
