<?php

use App\Http\Controllers\UserWebBookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Request\PromoCodeController;

Route::middleware('auth')->group(function () {
    Route::middleware(['permission:web-create-booking'])->get('/create-booking', [UserWebBookingController::class, 'booking'])->name('web-booking.create-booking');
    Route::post('/logout', [UserWebBookingController::class, 'logout'])->name('logout');
    Route::middleware(['permission:view-web-profile'])->get('/profile', [UserWebBookingController::class, 'profile'])->name('web-booking.profile');
    Route::post('/user/update-profile', [UserWebBookingController::class, 'updateProfile'])->name('user.updateProfile');
    Route::middleware(['permission:view-web-history'])->get('/history', [UserWebBookingController::class, 'history'])->name('web-booking.history');
    Route::middleware(['permission:view-web-history-detail'])->get('/history/view/{requestmodel}', [UserWebBookingController::class, 'viewDetails'])->name('history.viewDetails');
    Route::middleware('remove_empty_query')->get('/webuser/list', [UserWebBookingController::class, 'list'])->name('web-users.list');
    Route::middleware('remove_empty_query')->get('/create-booking', [UserWebBookingController::class, 'booking'])->name('web-booking.create-booking');
    Route::post('/web-create-request', [UserWebBookingController::class, 'createRequest']);

    Route::middleware(['permission:view-web-support'])->get('/get-support', [UserWebBookingController::class, 'getSupport'])->name('web-booking.getSupport');
    Route::middleware('remove_empty_query')->get('/get-support/list', [UserWebBookingController::class, 'supportList'])->name('web-users.supportList');
    Route::middleware(['permission:create-web-support-ticket'])->get('/create-ticket', [UserWebBookingController::class, 'createTicket'])->name('ticket.createTicket');
    Route::post('/ticket/store', [UserWebBookingController::class, 'store'])->name('ticket.store');
    Route::middleware(['permission:view-web-support-ticket-detail'])->get('/ticket/view/{supportTicket}', [UserWebBookingController::class, 'viewTicketDetails'])->name('tickethistory.viewDetails');
    Route::post('/ticket/reply/{supportTicket}', [UserWebBookingController::class, 'replyMessage'])->name('tickethistory.replyMessage');
    Route::get('/fetch-user', [UserWebBookingController::class, 'fetchUser'])->name('ticketchat.fetchUser');
    
    Route::get('user/promocode-list', [PromoCodeController::class, 'index']);
    Route::post('promocode-redeem', [PromoCodeController::class, 'redeem']);
    Route::post('promocode-clear', [PromoCodeController::class, 'clear']);
    
});
