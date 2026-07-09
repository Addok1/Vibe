<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // chat template
    Route::group(['prefix' => 'chat'], function () {
        Route::middleware(['permission:chat'])->get('/', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/fetch-user', [ChatController::class, 'fetchUser'])->name('chat.fetchUser');
        Route::get('/messages/{conversationId}', [ChatController::class, 'messages'])->name('chat.messages');
        Route::post('/createChat', [ChatController::class, 'createChat'])->name('chat.createChat');
        Route::post('/send-admin', [ChatController::class, 'sendAdmin'])->name('chat.sendAdmin');
        Route::post('/close-chat', [ChatController::class, 'closeChat'])->name('chat.closeChat');
        Route::get('/fetchChat', [ChatController::class, 'fetchChats'])->name('chat.fetchChats');
        Route::get('/readAll', [ChatController::class, 'readAll'])->name('chat.readAll');
        Route::get('/search-user', [ChatController::class, 'searchUser'])->name('chat.searchUser');
        Route::get('/verify-chat', [ChatController::class, 'verifyChat'])->name('chat.verifyChat');
    });
});
