<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //admin
    Route::group(['prefix' => 'admins', 'middleware' => 'permission:admin'], function () {
        Route::get('/', [AdminController::class, 'index'])->name('admins.index');
        Route::middleware('remove_empty_query')->get('/list', [AdminController::class, 'list'])->name('admins.list');
        Route::middleware(['permission:add-admin'])->get('/create', [AdminController::class, 'create'])->name('admins.create');
        Route::post('/store', [AdminController::class, 'store'])->name('admins.store');
        Route::post('/update/{adminDetail}', [AdminController::class, 'update'])->name('admins.update');
        Route::middleware(['permission:edit-admin'])->get('/edit/{adminDetail}', [AdminController::class, 'edit'])->name('admins.edit');
        Route::middleware(['permission:edt-admin'])->get('/password/edit/{adminDetail}', [AdminController::class, 'editPassword'])->name('adminDetail.password.edit');
        Route::post('/password/update/{adminDetail}', [AdminController::class, 'updatePasswords'])->name('adminDetail.password.update');
        Route::delete('/delete/{adminDetail}', [AdminController::class, 'destroy'])->name('admin.destroy');
        Route::post('/update-status', [AdminController::class, 'updateStatus'])->name('admin.updateDocumentStatus');
        Route::get('/check-mobile/{mobile}', [AdminController::class, 'checkMobileExists']);
        Route::get('/check-email/{email}', [AdminController::class, 'checkEmailExists']);
        Route::get('/check-mobile/{mobile}/{id}', [AdminController::class, 'checkMobileExists'])->name('admin.checkMobileExists');
        Route::get('/check-email/{email}/{id}', [AdminController::class, 'checkEmailExists'])->name('admin.checkEmailExists');

    });
});
