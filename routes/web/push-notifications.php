<?php

use App\Http\Controllers\PushNotificationController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //push notification
    Route::group(['prefix' => 'push-notifications', 'middleware' => 'permission:view-notifications'], function () {
        Route::get('/', [PushNotificationController::class, 'index'])->name('pushnotification.index');
        Route::middleware(['permission:add-notifications'])->get('/create', [PushNotificationController::class, 'create'])->name('pushnotification.create');
        Route::middleware('remove_empty_query')->get('/list', [PushNotificationController::class, 'fetch'])->name('pushnotification.list');
        Route::middleware(['permission:edit-notifications'])->get('/edit/{notification}', [PushNotificationController::class, 'edit'])->name('pushnotification.edit');
        Route::delete('/delete/{notification}', [PushNotificationController::class, 'delete'])->name('pushnotification.delete');
        Route::post('/send-push', [PushNotificationController::class, 'sendPush'])->name('pushnotification.send-push');
        Route::post('/update', [PushNotificationController::class, 'update'])->name('pushnotification.update');
    });
});
