<?php

use App\Http\Controllers\support\SupportTicketTitleController;
use App\Http\Controllers\support\SupportTicketsListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // support ticket title
    Route::group(['prefix' => 'title', 'middleware' => 'permission:view-ticket-title'], function () {
        Route::get('/', [SupportTicketTitleController::class, 'index'])->name('title.index');
        Route::middleware('remove_empty_query')->get('/list', [SupportTicketTitleController::class, 'list'])->name('title.list');
        Route::middleware(['permission:add-ticket-title'])->get('/create', [SupportTicketTitleController::class, 'create'])->name('title.create');
        Route::post('/store', [SupportTicketTitleController::class, 'store'])->name('title.store');
        Route::middleware(['permission:edit-ticket-title'])->get('/edit/{id}', [SupportTicketTitleController::class, 'edit'])->name('title.edit');
        Route::post('/update/{title}', [SupportTicketTitleController::class, 'update'])->name('title.update');
        Route::middleware(['permission:delete-ticket-title'])->delete('/delete/{title}', [SupportTicketTitleController::class, 'destroy']);
        Route::post('/update-status', [SupportTicketTitleController::class, 'updateStatus'])->name('title.updateStatus');
    });

    // support ticket list
    Route::group(['prefix' => 'support-tickets', 'middleware' => 'permission:view-support-ticket'], function () {
        Route::get('/', [SupportTicketsListController::class, 'index'])->name('support-tickets.index');
        Route::middleware('remove_empty_query')->get('/list', [SupportTicketsListController::class, 'list'])->name('support-tickets.list');
        Route::post('/update/{support_ticket}', [SupportTicketsListController::class, 'updateAssingTO'])->name('support-tickets.updateAssingTO');
        Route::get('/ticket-counts', [SupportTicketsListController::class, 'getTicketCounts']);
        Route::middleware('remove_empty_query')->get('/individual_list', [SupportTicketsListController::class, 'individualList'])->name('support-tickets.individualList');
        Route::get('/view-details/{supportTicket}', [SupportTicketsListController::class, 'viewTicketDetails'])->name('ticket.viewDetails');
        Route::post('/ticket/reply/{supportTicket}', [SupportTicketsListController::class, 'replyMessage'])->name('ticket.replyMessage');
        Route::post('/update-status', [SupportTicketsListController::class, 'updateStatus'])->name('ticket.updateStatus');
    });
});
